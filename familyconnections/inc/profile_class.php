<?php
/**
 * Profile.
 *
 * @copyright   2010 Haudenschilt LLC
 * @author      Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */
class Profile
{
    public $fcmsError;
    public $fcmsDatabase;
    public $fcmsUser;
    public $fcmsFamilyTree;
    public $fcmsAward;
    public $fcmsAddressBook;

    /**
     * __construct.
     *
     * @param FCMS_Error  $fcmsError
     * @param Database    $fcmsDatabase
     * @param User        $fcmsUser
     * @param FamilyTree  $fcmsFamilyTree
     * @param Awards      $fcmsAward
     * @param AddressBook $fcmsAddressBook
     *
     * @return void
     */
    public function __construct(FCMS_Error $fcmsError, Database $fcmsDatabase, User $fcmsUser, FamilyTree $fcmsFamilyTree, Awards $fcmsAward, $fcmsAddressBook = null)
    {
        $this->fcmsError = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser = $fcmsUser;
        $this->fcmsFamilyTree = $fcmsFamilyTree;
        $this->fcmsAward = $fcmsAward;
        $this->fcmsAddressBook = $fcmsAddressBook;
    }

    /**
     * displayEditProfile.
     *
     * @return void
     */
    public function displayEditProfile()
    {
        $stats = $this->getStats($this->fcmsUser->id);

        echo '
            <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/easy-pie-chart/2.1.4/jquery.easypiechart.min.js"></script>
            <div id="sections_menu">
                <ul>
                    <li><a href="profile.php">'.T_('View Stats').'</a></li>
                    <li><a href="?view=info">'.T_('Basic Information').'</a></li>
                    <li><a href="?view=picture">'.T_('Profile Picture').'</a></li>
                    <li><a href="?view=address">'.T_('Address / Contact').'</a></li>
                </ul>
            </div>
            <div id="actions_menu">
                <ul>
                    <li><a href="?member='.$this->fcmsUser->id.'">'.T_('View Profile').'</a></li>
                </ul>
            </div>
            <div class="info-alert">
                <h2>'.T_('Edit Profile').'</h2>
                <p>'.T_('Use the links above to Edit the different sections of your profile.').'</p>
            </div>
            <h2>'.T_('Stats').'</h2>
            <div id="stats">';

        foreach ($stats as $stat)
        {
            echo $stat;
        }

        echo '
            </div>
            <script type="text/javascript">
                $(function() {
                    $(".stat").easyPieChart({
                        animate     : false,
                        scaleColor  : false,
                        barColor    : "#99CEF0",
                        lineWidth   : 6,
                        size        : 150
                    });
                });
            </script>';
    }

    /**
     * displayEditBasicInfo.
     *
     * @return void
     */
    public function displayEditBasicInfo()
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
        $gender_options = buildHtmlSelectOptions(['M' => T_('Male'), 'F' => T_('Female')], $row['sex']);

        // Birthday
        $day_list = [];
        $i = 1;
        while ($i <= 31)
        {
            $day_list[$i] = $i;
            $i++;
        }
        $day_options = buildHtmlSelectOptions($day_list, $row['dob_day']);

        $month_list = [];
        $i = 1;
        while ($i <= 12)
        {
            $month_list[$i] = getMonthAbbr($i);
            $i++;
        }
        $month_options = buildHtmlSelectOptions($month_list, $row['dob_month']);

        $year_list = [];
        $i = 1900;
        $year_end = fixDate('Y', $this->fcmsUser->tzOffset);
        while ($i <= $year_end)
        {
            $year_list[$i] = $i;
            $i++;
        }
        $year_options = buildHtmlSelectOptions($year_list, $row['dob_year']);

        echo '
            <div id="sections_menu">
                <ul>
                    <li><a href="profile.php">'.T_('View Stats').'</a></li>
                    <li><a href="?view=info">'.T_('Basic Information').'</a></li>
                    <li><a href="?view=picture">'.T_('Profile Picture').'</a></li>
                    <li><a href="?view=address">'.T_('Address / Contact').'</a></li>
                </ul>
            </div>
            <div id="leftcolumn">
                <ul class="menu">
                    <li><a href="#section-name">'.T_('Name').'</a></li>
                    <li><a href="#section-bio">'.T_('Bio').'</a></li>
                    <li><a href="#section-gender">'.T_('Gender').'</a></li>
                    <li><a href="#section-birthday">'.T_('Birthday').'</a></li>
                </ul>
            </div>
            <div id="maincolumn">
                <script type="text/javascript" src="ui/js/livevalidation.js"></script>
                <form id="frm" action="profile.php?view=info" method="post">
                <fieldset id="section-name">
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
                <fieldset id="section-bio">
                    <legend><span>'.T_('Bio').'</span></legend>
                    <div class="field-row">
                        <div class="field-label"><label class="optional" for="bio"><b>'.T_('Bio').'</b></label></div>
                        <div class="field-widget">
                            <textarea name="bio" id="bio" cols="40" rows="5">'.$row['bio'].'</textarea>
                        </div>
                    </div>
                </fieldset>
                <fieldset id="section-gender">
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
                <fieldset id="section-birthday">
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
     * displayEditProfilePicture.
     *
     * @return void
     */
    public function displayEditProfilePicture()
    {
        echo '
            <div id="sections_menu">
                <ul>
                    <li><a href="profile.php">'.T_('View Stats').'</a></li>
                    <li><a href="?view=info">'.T_('Basic Information').'</a></li>
                    <li><a href="?view=picture">'.T_('Profile Picture').'</a></li>
                    <li><a href="?view=address">'.T_('Address / Contact').'</a></li>
                </ul>
            </div>
            <div id="leftcolumn">
                <br/>
            </div>
            <div id="maincolumn">';

        $profileClassName = getProfileClassName();
        $profileClassName .= 'Form';

        $profileForm = new $profileClassName($this->fcmsError, $this->fcmsDatabase, $this->fcmsUser);

        $profileForm->display();

        echo '
            </div>';
    }

    /**
     * displayEditAddress.
     *
     * @return void
     */
    public function displayEditAddress()
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
            <div id="sections_menu">
                <ul>
                    <li><a href="profile.php">'.T_('View Stats').'</a></li>
                    <li><a href="?view=info">'.T_('Basic Information').'</a></li>
                    <li><a href="?view=picture">'.T_('Profile Picture').'</a></li>
                    <li><a href="?view=address">'.T_('Address / Contact').'</a></li>
                </ul>
            </div>
            <div id="leftcolumn">
                <br/>
            </div>
            <div id="maincolumn">';

        $this->fcmsAddressBook->displayEditForm($address_id, '', 'profile.php?view=address');

        echo '
            </div>';
    }

    /**
     * getStats.
     *
     * @param int $userid
     *
     * @return void
     */
    public function getStats($userid)
    {
        $data = [];

        $postsCount = getPostsById($userid, 'array');
        $photosCount = getPhotosById($userid, 'array');
        $commentsCount = getCommentsById($userid, 'array');
        $calendarsCount = getCalendarEntriesById($userid, 'array');

        $data['posts'] = '
                    <div class="stat" data-percent="'.$postsCount['percent'].'">
                        <div class="label">'.T_('Posts').'</div>
                        <span class="inner" title="'.$postsCount['percent'].'%">'.$postsCount['count'].'</span>
                    </div>';

        $data['photos'] = '
                    <div class="stat" data-percent="'.$photosCount['percent'].'">
                        <div class="label">'.T_('Photos').'</div>
                        <span class="inner" title="'.$photosCount['percent'].'%">'.$photosCount['count'].'</span>
                    </div>';

        $data['comments'] = '
                    <div class="stat" data-percent="'.$commentsCount['percent'].'">
                        <div class="label">'.T_('Comments').'</div>
                        <span class="inner" title="'.$commentsCount['percent'].'%">'.$commentsCount['count'].'</span>
                    </div>';

        $data['events'] = '
                    <div class="stat" data-percent="'.$calendarsCount['percent'].'">
                        <div class="label">'.T_('Dates').'</div>
                        <span class="inner" title="'.$calendarsCount['percent'].'%">'.$calendarsCount['count'].'</span>
                    </div>';

        if (usingFamilyNews())
        {
            $newsCount = getFamilyNewsById($userid, 'array');

            $data['news'] = '
                    <div class="stat" data-percent="'.$newsCount['percent'].'">
                        <div class="label">'.T_('Family News').'</div>
                        <span class="inner" title="'.$newsCount['percent'].'%">'.$newsCount['count'].'</span>
                    </div>';
        }

        if (usingRecipes())
        {
            $recipesCount = getRecipesById($userid, 'array');

            $data['recipes'] = '
                    <div class="stat" data-percent="'.$recipesCount['percent'].'">
                        <div class="label">'.T_('Recipes').'</div>
                        <span class="inner" title="'.$recipesCount['percent'].'%">'.$recipesCount['count'].'</span>
                    </div>';
        }

        if (usingDocuments())
        {
            $documentsCount = getDocumentsById($userid, 'array');

            $data['documents'] = '
                    <div class="stat" data-percent="'.$documentsCount['percent'].'">
                        <div class="label">'.T_('Documents').'</div>
                        <span class="inner" title="'.$documentsCount['percent'].'%">'.$documentsCount['count'].'</span>
                    </div>';
        }
        if (usingPrayers())
        {
            $prayersCount = getPrayersById($userid, 'array');

            $data['prayers'] = '
                    <div class="stat" data-percent="'.$prayersCount['percent'].'">
                        <div class="label">'.T_('Prayer Concerns').'</div>
                        <span class="inner" title="'.$prayersCount['percent'].'%">'.$prayersCount['count'].'</span>
                    </div>';
        }

        return $data;
    }
}
