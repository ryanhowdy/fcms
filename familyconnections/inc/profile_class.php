<?php
/**
 * Profile 
 * 
 * @package     Family Connections
 * @copyright   2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Profile
{
    var $fcmsError;
    var $fcmsDatabase;
    var $fcmsUser;
    var $fcmsFamilyTree;
    var $fcmsAward;
    var $fcmsAddressBook;

    /**
     * Profile 
     * 
     * @param object $fcmsError 
     * @param object $fcmsDatabase
     * @param object $fcmsUser 
     * @param object $fcmsFamilyTree
     * @param object $fcmsAward
     * @param object $fcmsAddressBook
     *
     * @return  void
     */
    function Profile ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsFamilyTree, $fcmsAward, $fcmsAddressBook = null)
    {
        $this->fcmsError       = $fcmsError;
        $this->fcmsDatabase    = $fcmsDatabase;
        $this->fcmsUser        = $fcmsUser;
        $this->fcmsFamilyTree  = $fcmsFamilyTree;
        $this->fcmsAward       = $fcmsAward;
        $this->fcmsAddressBook = $fcmsAddressBook;
    }

    /**
     * displayEditProfile 
     * 
     * @return void
     */
    function displayEditProfile ()
    {
        $stats = $this->getStats($this->fcmsUser->id);

        echo '
            <div id="leftcolumn">
                <ul class="menu">
                    <li><a href="?view=info">'.T_('Basic Information').'</a></li>
                    <li><a href="?view=picture">'.T_('Profile Picture').'</a></li>
                    <li><a href="?view=address">'.T_('Address / Contact').'</a></li>
                </ul>
            </div>
            <div id="maincolumn">
                <div id="actions_menu">
                    <ul>
                        <li><a href="?member='.$this->fcmsUser->id.'">'.T_('View Profile').'</a></li>
                    </ul>
                </div>
                <h2>'.T_('Stats').'</h2>
                <div id="stats">';

        foreach ($stats as $stat)
        {
            echo $stat;
        }

        echo '
                </div>
            </div>';
    }

    /**
     * displayEditBasicInfo 
     * 
     * @return void
     */
    function displayEditBasicInfo ()
    {
        $sql = "SELECT `fname`, `mname`, `lname`, `maiden`, `bio`, `sex`, 
                    `dob_year`, `dob_month`, `dob_day`
                FROM `fcms_users`
                WHERE `id` = '".$this->fcmsUser->id."'";

        $row = $this->fcmsDatabase->getRow($sql, $this->fcmsUser->id);
        if ($row === false)
        {
           $this->fcmsError->displayError();
            return;
        }

        // Gender
        $gender_options = buildHtmlSelectOptions(array('M' => T_('Male'), 'F' => T_('Female')), $row['sex']);

        // Birthday
        $day_list = array();
        $i = 1;
        while ($i <= 31)
        {
            $day_list[$i] = $i;
            $i++;
        }
        $day_options = buildHtmlSelectOptions($day_list, $row['dob_day']);

        $month_list = array();
        $i = 1;
        while ($i <= 12)
        {
            $month_list[$i] = getMonthAbbr($i);
            $i++;
        }
        $month_options = buildHtmlSelectOptions($month_list, $row['dob_month']);

        $year_list = array();
        $i = 1900;
        $year_end = fixDate('Y', $this->fcmsUser->tzOffset);
        while ($i <= $year_end)
        {
            $year_list[$i] = $i;
            $i++;
        }
        $year_options = buildHtmlSelectOptions($year_list, $row['dob_year']);

        echo '
            <div id="leftcolumn">
                <ul class="menu">
                    <li><a href="?view=info">'.T_('Basic Information').'</a></li>
                    <li><a href="?view=picture">'.T_('Profile Picture').'</a></li>
                    <li><a href="?view=address">'.T_('Address / Contact').'</a></li>
                </ul>
            </div>
            <div id="maincolumn">
                <script type="text/javascript" src="ui/js/livevalidation.js"></script>
                <form id="frm" action="profile.php?view=info" method="post">
                <fieldset>
                    <legend><span>'.T_('Name').'</span></legend>
                    <div class="field-row">
                        <div class="field-label"><label for="fname"><b>'.T_('First').'</b></label></div>
                        <div class="field-widget">
                            <input type="text" name="fname" size="50" id="fname" value="'.cleanOutput($row['fname']).'"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var ffname = new LiveValidation(\'fname\', { onlyOnSubmit: true });
                        ffname.add(Validate.Presence, {failureMessage: "'.T_('Sorry, but this information is required.').'"});
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label class="optional" for="mname"><b>'.T_('Middle').'</b></label></div>
                        <div class="field-widget">
                            <input type="text" name="mname" size="50" id="mname" value="'.cleanOutput($row['mname']).'"/>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label"><label for="lname"><b>'.T_('Last').'</b></label></div>
                        <div class="field-widget">
                            <input type="text" name="lname" size="50" id="lname" value="'.cleanOutput($row['lname']).'"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var flname = new LiveValidation(\'lname\', { onlyOnSubmit: true });
                        flname.add(Validate.Presence, {failureMessage: "'.T_('Sorry, but this information is required.').'"});
                    </script>
                    <div class="field-row">
                        <div class="field-label"><label class="optional" for="maiden"><b>'.T_('Maiden').'</b></label></b></div>
                        <div class="field-widget">
                            <input type="text" name="maiden" size="50" id="maiden" value="'.cleanOutput($row['maiden']).'"/>
                        </div>
                    </div>
                </fieldset>
                <fieldset>
                    <legend><span>'.T_('Bio').'</span></legend>
                    <div class="field-row">
                        <div class="field-label"><label class="optional" for="bio"><b>'.T_('Bio').'</b></label></div>
                        <div class="field-widget">
                            <textarea name="bio" id="bio" cols="40" rows="5">'.$row['bio'].'</textarea>
                        </div>
                    </div>
                </fieldset>
                <fieldset>
                    <legend><span>'.T_('Gender').'</span></legend>
                    <div class="field-row">
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
                </fieldset>
                <fieldset>
                    <legend><span>'.T_('Birthday').'</span></legend>
                    <div class="field-row">
                        <div class="field-label"><label for="sday"><b>'.T_('Birthday').'</b></label></div>
                        <div class="field-widget">
                            <select id="sday" name="sday">
                                <option value="">'.T_('Day').'</option>
                                '.$day_options.'
                            </select>
                            <select id="smonth" name="smonth">
                                <option value="">'.T_('Month').'</option>
                                '.$month_options.'
                            </select>
                            <select id="syear" name="syear">
                                <option value="">'.T_('Year').'</option>
                                '.$year_options.'
                            </select>
                        </div>
                    </div>
                </fieldset>
                <p><input class="sub1" type="submit" name="submit" id="submit" value="'.T_('Submit').'"/></p>
                </form>
            </div>';
    }

    /**
     * displayEditProfilePicture 
     * 
     * @return void
     */
    function displayEditProfilePicture ()
    {
        $sql = "SELECT `avatar`, `gravatar` 
                FROM `fcms_users`
                WHERE `id` = ?";

        $row = $this->fcmsDatabase->getRow($sql, $this->fcmsUser->id);
        if ($row === false)
        {
           $this->fcmsError->displayError();

            return;
        }

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

        $form   = '';
        $input  = '';
        $js     = '';
        $submit = 'submit';

        if (usingAdvancedUploader($this->fcmsUser->id))
        {
            $submit = 'button';
            $form   = '<form id="frm" name="frm" method="post">';

            $input = '<applet name="jumpLoaderApplet"
                    code="jmaster.jumploader.app.JumpLoaderApplet.class"
                    archive="inc/thirdparty/jumploader_z.jar"
                    width="200"
                    height="260"
                    mayscript>
                    <param name="uc_sendImageMetadata" value="true"/>
                    <param name="uc_maxFiles" value="1"/>
                    <param name="uc_uploadUrl" value="profile.php?advanced-avatar=true&amp;orig='.$row['avatar'].'"/>
                    <param name="vc_useThumbs" value="true"/>
                    <param name="uc_uploadScaledImagesNoZip" value="true"/>
                    <param name="uc_uploadScaledImages" value="true"/>
                    <param name="uc_scaledInstanceNames" value="avatar"/>
                    <param name="uc_scaledInstanceDimensions" value="80x80xcrop"/>
                    <param name="uc_scaledInstanceQualityFactors" value="900"/>
                    <param name="uc_uploadFormName" value="frm"/>
                    <param name="vc_lookAndFeel" value="system"/>
                    <param name="vc_uploadViewStartActionVisible" value="false"/>
                    <param name="vc_uploadViewStopActionVisible" value="false"/>
                    <param name="vc_uploadViewPasteActionVisible" value="false"/>
                    <param name="vc_uploadViewRetryActionVisible" value="false"/>
                    <param name="vc_uploadViewFilesSummaryBarVisible" value="false"/>
                    <param name="vc_uiDefaults" value="Panel.background=#eff0f4; List.background=#eff0f4;"/> 
                    <param name="ac_fireUploaderStatusChanged" value="true"/> 
                </applet>
                <br/>';

            $js = '<script language="javascript">
                Event.observe("submitUpload","click",function(){
                    if ($(\'fcms\').visible()) {
                        var uploader = document.jumpLoaderApplet.getUploader();
                        uploader.startUpload();
                    }
                });
                function uploaderStatusChanged(uploader) {
                    if (uploader.isReady() && uploader.getFileCountByStatus(3) == 0) { 
                        window.location.href = "profile.php?view=picture";
                    }
                }
                </script>';
        }
        else
        {
            $form  = '<form id="frm" name="frm" enctype="multipart/form-data" action="profile.php?view=picture" method="post">';

            $input = '<input type="file" name="avatar" id="avatar" size="30" title="'.T_('Upload your personal image (Avatar)').'"/>';
        }

        $currentAvatar = '<img id="current-avatar" src="'.getCurrentAvatar($this->fcmsUser->id).'" alt="'.T_('This is your current avatar.').'"/>';

        echo '
            <div id="leftcolumn">
                <ul class="menu">
                    <li><a href="?view=info">'.T_('Basic Information').'</a></li>
                    <li><a href="?view=picture">'.T_('Profile Picture').'</a></li>
                    <li><a href="?view=address">'.T_('Address / Contact').'</a></li>
                </ul>
            </div>
            <div id="maincolumn">
                '.$form.'
                    <fieldset>
                        <legend><span>'.T_('Profile Picture').'</span></legend>

                        <div class="field-row">
                            <div class="field-label">
                                <label for="avatar"><b>'.T_('Avatar').'</b></label>
                            </div>
                            <div class="field-widget">
                                <select name="avatar_type" id="avatar_type">
                                    '.$avatar_options.'
                                </select><br/>
                            </div>
                        </div>

                        <div id="fcms" class="field-row">
                            <div class="field-label">&nbsp;</div>
                            <div class="field-widget">
                                '.$input.'
                                <input type="hidden" name="avatar_orig" value="'.cleanOutput($row['avatar']).'"/><br/>
                                '.$currentAvatar.'
                            </div>
                            <p><input class="sub1" type="'.$submit.'" name="submit" id="submitUpload" value="'.T_('Submit').'"/></p>
                        </div>

                        <div id="gravatar" class="field-row">
                            <div class="field-label">&nbsp;</div>
                            <div class="field-widget">
                                <b>'.T_('Gravatar Email').'</b><br/>
                                <input type="text" name="gravatar_email" size="30" value="'.cleanOutput($row['gravatar']).'"/><br/>
                                '.$currentAvatar.'
                            </div>
                            <p><input class="sub1" type="submit" name="submit" id="submitGravatar" value="'.T_('Submit').'"/></p>
                        </div>

                        <div id="default" class="field-row">
                            <div class="field-label">&nbsp;</div>
                            <div class="field-widget">
                                '.$currentAvatar.'
                            </div>
                            <p><input class="sub1" type="submit" name="submit" id="submitDefault" value="'.T_('Submit').'"/></p>
                        </div>

                    </fieldset>
                </form>
                '.$js.'
            </div>';
    }

    /**
     * displayEditAddress 
     * 
     * @return void
     */
    function displayEditAddress ()
    {
        $sql = "SELECT `id`
                FROM `fcms_address`
                WHERE `user` = '".$this->fcmsUser->id."'";

        $row = $this->fcmsDatabase->getRow($sql, $this->fcmsUser->id);
        if ($row === false)
        {
            $this->fcmsError->displayError();

            return;
        }

        $address_id = $row['id'];

        echo '
            <div id="leftcolumn">
                <ul class="menu">
                    <li><a href="?view=info">'.T_('Basic Information').'</a></li>
                    <li><a href="?view=picture">'.T_('Profile Picture').'</a></li>
                    <li><a href="?view=address">'.T_('Address / Contact').'</a></li>
                </ul>
            </div>
            <div id="maincolumn">';

        $this->fcmsAddressBook->displayEditForm($address_id, '', 'profile.php?view=address');

        echo '
            </div>';
    }

    /**
     * getStats 
     * 
     * @param int $userid 
     * 
     * @return void
     */
    function getStats ($userid)
    {
        $data = array();

        $postsCount     = getPostsById($userid, 'array');
        $photosCount    = getPhotosById($userid, 'array');
        $commentsCount  = getCommentsById($userid, 'array');
        $calendarsCount = getCalendarEntriesById($userid, 'array');

        $data['posts'] = '<div class="stat c1">
                        <span title="'.$postsCount['percent'].' of total">'.$postsCount['count'].'</span>
                        <b>'.T_('Posts').'</b>
                    </div>';
        $data['photos'] = '<div class="stat c2">
                        <span title="'.$photosCount['percent'].' of total">'.$photosCount['count'].'</span>
                        <b>'.T_('Photos').'</b>
                    </div>';
        $data['comments'] = '<div class="stat c3">
                        <span title="'.$commentsCount['percent'].' of total">'.$commentsCount['count'].'</span>
                        <b>'.T_('Comments').'</b>
                    </div>';
        $data['events'] = '<div class="stat c4">
                        <span title="'.$calendarsCount['percent'].' of total">'.$calendarsCount['count'].'</span>
                        <b>'.T_('Dates').'</b>
                    </div>';

        $i = 5;

        if (usingFamilyNews())
        {
            $newsCount = getFamilyNewsById($userid, 'array');
            $data['news'] = '<div class="stat c'.$i.'">
                        <span title="'.$newsCount['percent'].' of total">'.$newsCount['count'].'</span>
                        <b>'.T_('Family News').'</b>
                    </div>';
            $i++;
        }
        if (usingRecipes())
        {
            $recipesCount = getRecipesById($userid, 'array');
            $data['recipes'] = '<div class="stat c'.$i.'">
                        <span title="'.$recipesCount['percent'].' of total">'.$recipesCount['count'].'</span>
                        <b>'.T_('Recipes').'</b>
                    </div>';
            $i++;
        }
        if (usingDocuments())
        {
            $documentsCount = getDocumentsById($userid, 'array');
            $data['documents'] = '<div class="stat c'.$i.'">
                        <span title="'.$documentsCount['percent'].' of total">'.$documentsCount['count'].'</span>
                        <b>'.T_('Documents').'</b>
                    </div>';
            $i++;
        }
        if (usingPrayers())
        {
            $prayersCount = getPrayersById($userid, 'array');
            $data['prayers'] = '<div class="stat c'.$i.'">
                        <span title="'.$prayersCount['percent'].' of total">'.$prayersCount['count'].'</span>
                        <b>'.T_('Prayer Concerns').'</b>
                    </div>';
            $i++;
        }

        return $data;
    }
}
