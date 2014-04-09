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
        echo '
            <div id="leftcolumn">
                <ul class="menu">
                    <li><a href="?view=info">'.T_('Basic Information').'</a></li>
                    <li><a href="?view=picture">'.T_('Profile Picture').'</a></li>
                    <li><a href="?view=address">'.T_('Address / Contact').'</a></li>
                </ul>
            </div>
            <div id="maincolumn">';

        $profileClassName  = getProfileClassName();
        $profileClassName .= 'Form';

        $profileForm = new $profileClassName($this->fcmsError, $this->fcmsDatabase, $this->fcmsUser);

        $profileForm->display();

        echo '
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
