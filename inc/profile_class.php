<?php
include_once('database_class.php');
include_once('utils.php');
include_once('datetime.php');
include_once('familytree_class.php');
include_once('awards_class.php');

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
    var $db;
    var $db2;
    var $treeObj;
    var $awardObj;
    var $tzOffset;
    var $currentUserId;

    /**
     * Profile 
     * 
     * @param int $currentUserId 
     *
     * @return  void
     */
    function Profile ($currentUserId)
    {
        global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

        $this->db  = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $this->db2 = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);

        $this->currentUserId = cleanInput($currentUserId, 'int');
        $this->tzOffset      = getTimezone($this->currentUserId);
        $this->awardObj      = new Awards($this->currentUserId);
        $this->treeObj       = new FamilyTree($this->currentUserId);
    }

    /**
     * displayProfile 
     * 
     * @param int $userid 
     * 
     * @return  void
     */
    function displayProfile ($userid)
    {
        // Check for valid user id
        if (!ctype_digit($userid)) {
            echo '
            <p class="error-alert">'.T_('Invalid User').'</p>';
            return;
        }

        $sql = "SELECT u.fname, u.lname, u.email, u.`dob_year`, u.`dob_month`, u.`dob_day`, u.avatar, u.username, u.joindate, u.activity, 
                    u.`sex`, a.`id` AS aid, a.`address`, a.`city`, a.`state`, a.`zip`, a.`home`, a.`cell`, a.`work`  
                FROM fcms_users AS u, fcms_address AS a 
                WHERE u.id = '$userid' 
                AND u.id = a.user";

        if (!$this->db->query($sql))
        {
            displaySQLError('Profile Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        }

        $row = $this->db->get_row();

        // Rank Info
        $points = getUserParticipationPoints($userid);
        $level  = getUserParticipationLevel($points);
        $pts = 0;

        // Dates Info
        $joinDate     = fixDate(T_('F j, Y'), $this->tzOffset, $row['joindate']);
        $activityDate = fixDate(T_('F j, Y g:i a'), $this->tzOffset, $row['activity']);

        // Stats Info -- if user is not a guest
        if (checkAccess($this->currentUserId) != 10)
        {
            $statsData = $this->getStats($userid);
        }

        // Address
        $address = '';
        if (empty($row['address']) && empty($row['state']))
        {
            $address = "<i>(".T_('none').")</i>";
        }
        else
        {
            if (!empty($row['address']))
            {
                $address .= $row['address'] . "<br/>";
            }
            if (!empty($row['city']))
            {
                $address .= $row['city'] . ", ";
            }
            $address .= $row['state'] . " " . $row['zip'];
        }

        // Phone Numbers
        $home = empty($row['home']) ? "<i>(" . T_('none') . ")</i>" : $row['home'];
        $work = empty($row['work']) ? "<i>(" . T_('none') . ")</i>" : $row['work'];
        $cell = empty($row['cell']) ? "<i>(" . T_('none') . ")</i>" : $row['cell'];

        // Sex
        $sex = ($row['sex'] == 'F') ? 'female' : 'male';

        // Print the profile info
        echo '
            <div id="side-info" class="' . $sex . '">
                <img class="avatar" src="'.getCurrentAvatar($userid).'" alt="avatar"/>
                <div class="name">
                    <h3>'.$row['fname'].' '.$row['lname'].'</h3>
                    <h4>'.$row['username'].'</h4>
                    '.$level.'
                </div>
                <p>
                    <a class="action" href="privatemsg.php?compose=new&amp;id='.$userid.'">'.T_('Send PM').'</a>
                </p>
                <b>'.T_('Address').'</b>
                <br/>'.$address.'<br/>
                <p>
                    <b>'.T_('Home Phone').'</b>: '.$home.'<br/>
                    <b>'.T_('Work Phone').'</b>: '.$work.'<br/>
                    <b>'.T_('Cell Phone').'</b>: '.$cell.'<br/>
                </p>
                <hr/>
                <h4>'.T_('Participation Points').'</h4>
                <p>
                    <b>'.T_('Points').':</b> '.$points.'<br/>
                </p>
                <hr/>
                <p><b>'.T_('Join Date').':</b><br/>'.$joinDate.'</p>
                <p><b>'.T_('Last Visit').':</b><br/>'.$activityDate.'</p>
                <hr/>
                <h4>'.T_('Relationships').'</h4>';

        // Display Family Tree Listing
        $this->treeObj->displayFamilyTree($userid, 'list');

        echo '
                <p><a href="familytree.php?tree=' . $userid . '">' . T_('View Family Tree') . '</a></p>
            </div>
            <div id="main-info">
                <h2>'.T_('Stats').'</h2>
                <div id="stats" class="clearfix">';

        foreach ($statsData as $stats)
        {
            echo $stats;
        }

        echo '
                </div>
                <h2>'.T_('Awards').'</h2>';

        $this->awardObj->displayAwards($userid);

        echo '
                <h2>'.T_('Last 5 Posts').'</h2>';
        $this->displayLast5Posts($userid);

        echo '
                <h2>'.T_('Last 5 Photos').'</h2>';
        $this->displayLast5Photos($userid);

        echo '
            </div>
            <div style="clear:both"></div>';
    }

    /**
     * displayEditProfile 
     * 
     * @return void
     */
    function displayEditProfile ()
    {
        $stats = $this->getStats($this->currentUserId);

        echo '
            <div id="leftcolumn">
                <ul class="menu">
                    <li><a href="?view=info">'.T_('Basic Information').'</a></li>
                    <li><a href="?view=picture">'.T_('Profile Picture').'</a></li>
                    <li><a href="?view=address">'.T_('Address / Contact').'</a></li>
                </ul>
            </div>
            <div id="maincolumn">
                <div id="actions_menu" class="clearfix">
                    <ul>
                        <li><a href="?member='.$this->currentUserId.'">'.T_('View Profile').'</a></li>
                    </ul>
                </div>
                <h2>'.T_('Stats').'</h2>
                <div id="stats" class="clearfix">';

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
                WHERE `id` = '".$this->currentUserId."'";
        $this->db->query($sql) or displaySQLError(
            'Settings Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();

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
        $year_end = fixDate('Y', $this->tzOffset);
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
                <script type="text/javascript" src="inc/js/livevalidation.js"></script>
                <form id="frm" action="profile.php?view=info" method="post">
                <fieldset>
                    <legend><span>'.T_('Name').'</span></legend>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="fname"><b>'.T_('First').'</b></label></div>
                        <div class="field-widget">
                            <input type="text" name="fname" size="50" id="fname" value="'.cleanOutput($row['fname']).'"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var ffname = new LiveValidation(\'fname\', { onlyOnSubmit: true });
                        ffname.add(Validate.Presence, {failureMessage: "'.T_('Sorry, but this information is required.').'"});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label class="optional" for="mname"><b>'.T_('Middle').'</b></label></div>
                        <div class="field-widget">
                            <input type="text" name="mname" size="50" id="mname" value="'.cleanOutput($row['mname']).'"/>
                        </div>
                    </div>
                    <div class="field-row clearfix">
                        <div class="field-label"><label for="lname"><b>'.T_('Last').'</b></label></div>
                        <div class="field-widget">
                            <input type="text" name="lname" size="50" id="lname" value="'.cleanOutput($row['lname']).'"/>
                        </div>
                    </div>
                    <script type="text/javascript">
                        var flname = new LiveValidation(\'lname\', { onlyOnSubmit: true });
                        flname.add(Validate.Presence, {failureMessage: "'.T_('Sorry, but this information is required.').'"});
                    </script>
                    <div class="field-row clearfix">
                        <div class="field-label"><label class="optional" for="maiden"><b>'.T_('Maiden').'</b></label></b></div>
                        <div class="field-widget">
                            <input type="text" name="maiden" size="50" id="maiden" value="'.cleanOutput($row['maiden']).'"/>
                        </div>
                    </div>
                </fieldset>
                <fieldset>
                    <legend><span>'.T_('Bio').'</span></legend>
                    <div class="field-row clearfix">
                        <div class="field-label"><label class="optional" for="bio"><b>'.T_('Bio').'</b></label></div>
                        <div class="field-widget">
                            <textarea name="bio" id="bio" cols="40" rows="5">'.cleanOutput($row['bio']).'</textarea>
                        </div>
                    </div>
                </fieldset>
                <fieldset>
                    <legend><span>'.T_('Gender').'</span></legend>
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
                </fieldset>
                <fieldset>
                    <legend><span>'.T_('Birthday').'</span></legend>
                    <div class="field-row clearfix">
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
                WHERE `id` = '" . $this->currentUserId . "'";
        $this->db->query($sql) or displaySQLError(
            'Settings Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        $row = $this->db->get_row();

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

        if (usingAdvancedUploader($this->currentUserId))
        {
            $form  = '<form id="frm" name="frm" method="post">';

            $input = '<applet name="jumpLoaderApplet"
                    code="jmaster.jumploader.app.JumpLoaderApplet.class"
                    archive="inc/jumploader_z.jar"
                    width="200"
                    height="260"
                    mayscript>
                    <param name="uc_sendImageMetadata" value="true"/>
                    <param name="uc_maxFiles" value="1"/>
                    <param name="uc_uploadUrl" value="profile.php?advanced-avatar=true"/>
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
                Event.observe("submit","click",function(){
                    if ($(\'fcms\').visible()) {
                        var uploader = document.jumpLoaderApplet.getUploader();
                        uploader.startUpload();
                    }
                });
                function uploaderStatusChanged(uploader) {
                    if (uploader.isReady() && uploader.getFileCountByStatus(3) == 0) { 
                        window.location.href = "profile.php?view=advanced-picture&avatar_orig='.cleanOutput($row['avatar']).'";
                    }
                }
                </script>';
        }
        else
        {
            $form  = '<form id="frm" name="frm" enctype="multipart/form-data" action="profile.php?view=picture" method="post">';

            $input = '<input type="file" name="avatar" id="avatar" size="30" 
                title="'.T_('Upload your personal image (Avatar)').'"/>';
        }

        $currentAvatar = '<img id="current-avatar" src="'.getCurrentAvatar($this->currentUserId).'" alt="'.T_('This is your current avatar.').'"/>';

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

                        <div class="field-row clearfix">
                            <div class="field-label">
                                <label for="avatar"><b>'.T_('Avatar').'</b></label>
                            </div>
                            <div class="field-widget">
                                <select name="avatar_type" id="avatar_type">
                                    '.$avatar_options.'
                                </select><br/>
                            </div>
                        </div>

                        <div id="fcms" class="field-row clearfix">
                            <div class="field-label">&nbsp;</div>
                            <div class="field-widget">
                                '.$input.'
                                <input type="hidden" name="avatar_orig" value="'.cleanOutput($row['avatar']).'"/><br/>
                                '.$currentAvatar.'
                            </div>
                            <p><input class="sub1" type="button" name="submit" id="submit" value="'.T_('Submit').'"/></p>
                        </div>

                        <div id="gravatar" class="field-row clearfix">
                            <div class="field-label">&nbsp;</div>
                            <div class="field-widget">
                                <b>'.T_('Gravatar Email').'</b><br/>
                                <input type="text" name="gravatar_email" size="30" value="'.cleanOutput($row['gravatar']).'"/><br/>
                                '.$currentAvatar.'
                            </div>
                            <p><input class="sub1" type="submit" name="submit" id="submit" value="'.T_('Submit').'"/></p>
                        </div>

                        <div id="default" class="field-row clearfix">
                            <div class="field-label">&nbsp;</div>
                            <div class="field-widget">
                                '.$currentAvatar.'
                            </div>
                            <p><input class="sub1" type="submit" name="submit" id="submit" value="'.T_('Submit').'"/></p>
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
        require_once 'inc/addressbook_class.php';

        global $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass;

        $database = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $book = new AddressBook($this->currentUserId, $database);

        $sql = "SELECT `id`
                FROM `fcms_address`
                WHERE `user` = '".$this->currentUserId."'";
        if (!$this->db->query($sql)) {
            displaySQLError('Address Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error());
            return;
        }

        $row = $this->db->get_row();

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

        $book->displayEditForm($address_id, '', 'profile.php?view=address');

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

    /**
     * displayPointsToGo 
     *
     * Shows how many more points are needed for the next rank.
     *
     * @deprecated
     * @param       string  $pts 
     *
     * @return      void
     */
    function displayPointsToGo ($pts)
    {
        // Removed in 2.2
        $posts = ceil($pts / (1 / 75));
        $photos = ceil($pts / (1 / 25));
        $comments = ceil($pts / (1 / 20));
        $calendar = ceil($pts / (1 / 5));
        $news = ceil($pts / (1 / 10));
        echo '
                <div><small><i>&nbsp;&nbsp;&nbsp; '.$posts.' new posts &nbsp;&nbsp;- or -</i></small></div>
                <div><small><i>&nbsp;&nbsp;&nbsp; '.$photos.' new photos &nbsp;&nbsp;- or -</i></small></div>
                <div><small><i>&nbsp;&nbsp;&nbsp; '.$comments.' new comments &nbsp;&nbsp;- or -</i></small></div>
                <div><small><i>&nbsp;&nbsp;&nbsp; '.$calendar.' new calendar entries &nbsp;&nbsp;- or -</i></small></div>
                <div><small><i>&nbsp;&nbsp;&nbsp; '.$comments.' new family news entries &nbsp;&nbsp;- or -</i></small></div>';
    }

    /**
     * displayLast5Posts 
     * 
     * @param   int     $userid 
     * @return  void
     */
    function displayLast5Posts ($userid)
    {
        $userid = cleanInput($userid, 'int');

        $sql = "SELECT t.`id`, `subject`, `date`, `post` 
                FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t, `fcms_users` AS u 
                WHERE t.`id` = p.`thread` 
                AND p.`user` = u.`id` 
                AND u.`id` = '$userid' 
                ORDER BY `date` DESC 
                LIMIT 0, 5";
        $this->db2->query($sql) or displaySQLError(
            'Posts Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db2->count_rows() > 0) {
            while ($row = $this->db2->get_row()) {
                $date = fixDate(T_('F j, Y, g:i a'), $this->tzOffset, $row['date']);
                $post = removeBBCode($row['post']);
                $subject = stripslashes($row['subject']);
                $pos = strpos($subject, '#ANOUNCE#');
                if ($pos !== false) {
                    $subject = substr($subject, 9, strlen($subject)-9);
                }
                echo '
                <p>
                    <a href="messageboard.php?thread='.(int)$row['id'].'">'.$subject.'</a> 
                    <span class="date">'.$date.'</span><br/>
                    '.$post.'
                </p>';
            }
        } else {
            echo '
                <p>'.T_('none').'</p>';
        }
    }

    /**
     * displayLast5Photos 
     * 
     * @param   int     $userid 
     * @return  void
     */
    function displayLast5Photos ($userid)
    {
        $userid = cleanInput($userid, 'int');

        $sql = "SELECT * 
                FROM `fcms_gallery_photos` 
                WHERE user = '$userid' 
                ORDER BY `date` DESC 
                LIMIT 5";
        $this->db2->query($sql) or displaySQLError(
            'Photos Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db2->count_rows() > 0) {
            echo '
            <ul class="photos clearfix">';
            while ($row = $this->db2->get_row()) {
                echo '
                <li class="photo">
                    <a href="gallery/index.php?uid='.$userid.'&amp;cid='.(int)$row['category'].'&amp;pid='.(int)$row['id'].'">
                        <img class="photo" src="uploads/photos/member'.(int)$row['user'].'/tb_'.basename($row['filename']).'" alt=""/>
                    </a>
                </li>';
            }
            echo '
            </ul>';
        } else {
            echo "<p>".T_('none')."</p>";
        }
    }

}
