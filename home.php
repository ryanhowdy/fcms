<?php
if (!isset($_SESSION)) {
    session_start();
}
if (get_magic_quotes_gpc()) {
    $_REQUEST = array_map('stripslashes', $_REQUEST);
    $_GET = array_map('stripslashes', $_GET);
    $_POST = array_map('stripslashes', $_POST);
    $_COOKIE = array_map('stripslashes', $_COOKIE);
}
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/locale.php');
$locale = new Locale();

// Check that the user is logged in
isLoggedIn();
$current_user_id = (int)escape_string($_SESSION['login_id']);

header("Cache-control: private");

// include classes
include_once('inc/calendar_class.php');
include_once('inc/poll_class.php');
include_once('inc/database_class.php');
include_once('inc/alerts_class.php');
mysql_query("UPDATE `fcms_users` SET `activity`=NOW() WHERE `id` = $current_user_id");
$calendar = new Calendar($current_user_id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$poll = new Poll($current_user_id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$database = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$alert = new Alerts($database);

// Setup the Template variables;
$TMPL['pagetitle'] = _('Home');
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";
$TMPL['javascript'] = '
<script type="text/javascript" src="inc/tablesort.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initLatestInfoHighlight();
});
//]]>
</script>';

// Show Header
include_once(getTheme($current_user_id) . 'header.php');

echo '
        <div id="home" class="centercontent">

            <div id="leftcolumn">
                <h2 class="calmenu">'._('Calendar').'</h2>';
$year  = isset($_GET['year']) ? $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? str_pad($_GET['month'], 2, 0, STR_PAD_LEFT) : date('m');
$day = isset($_GET['day']) ? str_pad($_GET['day'], 2, 0, STR_PAD_LEFT) : date('d');
$calendar->displayCalendar($month, $year, $day);
$calendar->displayMonthEvents($month, $year);
$month = date("m", mktime(0,0,0,$month+1,1,2006));
$calendar->displayMonthEvents($month, $year);
if (!isset($_POST['vote']) && !isset($_GET['poll_id']) && !isset($_GET['action'])) {
    $poll->displayPoll('0', false);
}
echo '
                <h2 class="membermenu">'._('Members Online').'</h2>
                <div class="membermenu">';
displayMembersOnline();
echo '
                </div>
            </div>

            <div id="maincolumn">';
$showWhatsNew = true;

// Show Poll, hide everything else
if(isset($_GET['poll_id']) && !isset($_GET['action']) && !isset($_POST['vote'])) {
    // Santizing user input - poll_id - only allow digits 0-9
    if (preg_match('/^\d+$/', $_GET['poll_id'])) {
        $showWhatsNew = false;
        $poll->displayPoll(escape_string($_GET['poll_id']));
    }
}
if (isset($_POST['vote'])) {
    $showWhatsNew = false;
    if (isset($_GET['poll_id'])) {
        $poll_id = $_GET['poll_id'];
    } else {
        $poll_id = $_POST['poll_id'];
    }
    $poll->placeVote($current_user_id, $_POST['option_id'], $poll_id);
    $poll->displayResults($poll_id);
}
if (isset($_GET['action'])) {
    $showWhatsNew = false;
    if ($_GET['action'] == "results") {
        $poll_id = $_GET['poll_id'] ? $_GET['poll_id'] : $_POST['poll_id'];
        // Santizing user input - poll_id - only allow digits 0-9
        if (preg_match('/^\d+$/', $poll_id)) {
            $poll->displayResults($poll_id);
        } else {
            $showWhatsNew = true;
        }
    } elseif ($_GET['action'] == "pastpolls") {
        $page = 1;
        if (isset($_GET['page'])) { $page = $_GET['page']; }
        $poll->displayPastPolls($page);
    }
}

// If were not showing poll stuff, show the latest info
if ($showWhatsNew) {

    // Remove an alert
    if (isset($_GET['alert'])) {
        $sql = "INSERT INTO `fcms_alerts` (`alert`, `user`)
                VALUES (
                    '".escape_string($_GET['alert'])."', 
                    $current_user_id
                )";
        mysql_query($sql) or displaySQLError(
            'Remove Alert Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
    }

    // Show Alerts
    $alert->displayNewAdminHome($current_user_id);
    $alert->displayNewUserHome($current_user_id);

    // Show any events happening today
    $month = isset($_GET['month']) ? str_pad($_GET['month'], 2, 0, STR_PAD_LEFT) : date('m');
    $calendar->displayTodaysEvents($month, $day, $year);

    // Show what's new based on user's settings
    echo '
                <h2>'._('What\'s New').'</h2>';
    $sql = "SELECT `frontpage` FROM `fcms_user_settings` "
         . "WHERE `user` = $current_user_id";
    $result = mysql_query($sql) or displaySQLError(
        'Frontpage Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    mysql_free_result($result);

    // All by date
    if ($r['frontpage'] < 2) {
        displayWhatsNewAll($current_user_id);

    // Last 5 by category
    } else {
        include_once('inc/messageboard_class.php');
        include_once('inc/addressbook_class.php');
        include_once('inc/familynews_class.php');
        include_once('inc/gallery_class.php');
        include_once('inc/prayers_class.php');
        include_once('inc/recipes_class.php');
        include_once('inc/documents_class.php');
        include_once('inc/database_class.php');
        $database = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $mboard = new MessageBoard($current_user_id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $book = new AddressBook($current_user_id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $news = new FamilyNews($current_user_id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $gallery = new PhotoGallery($current_user_id, $database);
        $prayers = new Prayers($current_user_id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $recs = new Recipes($current_user_id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $docs = new Documents($current_user_id, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        echo '
                <div class="half">';
        $today = date('Y-m-d');
        $tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
        $mboard->displayWhatsNewMessageBoard();
        if (usingFamilyNews()) {
            $news->displayWhatsNewFamilyNews();
        }
        $book->displayWhatsNewAddressBook();
        if (usingRecipes()) {
            $recs->displayWhatsNewRecipes();
        }
        if (usingPrayers()) {
            $prayers->displayWhatsNewPrayers();
        }
        echo '
                </div>
                <div class="half">';
        $gallery->displayWhatsNewGallery();
        echo '
                    <h3>'._('Comments').'</h3>
                    <ul>';
        $sql_comments = '';
        if (usingFamilyNews()) {
            $sql_comments = "SELECT n.`user` AS 'id', n.`id` as 'id2', `comment`, "
                             . "nc.`date`, nc.`user`, 'NEWS' AS 'check' "
                          . "FROM `fcms_news_comments` AS nc, `fcms_news` AS n, "
                             . "`fcms_users` AS u "
                          . "WHERE nc.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) "
                          . "AND nc.`user` = u.`id` "
                          . "AND n.`id` = nc.`news` "
                          . "UNION ";
        }
        $sql_comments .= "SELECT `filename` AS 'id', p.`id` as 'id2', `comment`, "
                          . "gc.`date`, gc.`user`, `category` AS 'check' "
                       . "FROM `fcms_gallery_comments` AS gc, `fcms_users` AS u, "
                          . "`fcms_gallery_photos` AS p "
                       . "WHERE gc.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) "
                       . "AND gc.`user` = u.`id` "
                       . "AND gc.`photo` = p.`id` "
                       . "ORDER BY `date` DESC LIMIT 5";
        $result = mysql_query($sql_comments);
        if (mysql_num_rows($result) > 0) {
            while ($com = mysql_fetch_array($result)) {
                $date = $locale->fixDate('M. j, Y, g:i a', $mboard->tz_offset, $com['date']);
                $comment = $com['comment'];
                if (strlen($comment) > 30) {
                    $comment = substr($comment, 0, 27) . "...";
                }
                if (
                    strtotime($com['date']) >= strtotime($today) && 
                    strtotime($com['date']) < strtotime($tomorrow)
                ) {
                    $full_date = _('Today');
                    $d = ' class="today"';
                } else {
                    $full_date = $date;
                    $d = '';
                }
                if ($com['check'] !== 'NEWS') {
                    $url = 'gallery/index.php?uid=0&amp;cid=comments&amp;pid='.$com['id2'];
                } else {
                    $url = 'familynews.php?getnews='.$com['id'].'&amp;newsid='.$com['id2'];
                }
                $user = htmlentities($com['user'], ENT_COMPAT, 'UTF-8');
                $userName = getUserDisplayName($com['user']);
                echo '
                        <li>
                            <div'.$d.'>'.$full_date.'</div>
                            <a href="'.$url.'">'.$comment.'</a> - <a href="profile.php?member='.$user.'" class="u">'.$userName.'</a>
                        </li>';
            }
        } else {
            echo '
                        <li><i>'._('nothing new last 30 days').'</i></li>';
        }
        echo '
                    </ul>';
        $calendar->displayWhatsNewCalendar();
        if (usingDocuments()) {
            $docs->displayWhatsNewDocuments();
        }
        echo '
                </div>';
    }
    echo '
                <p class="alignright">
                    <a class="rss" href="rss.php?feed=all">'._('RSS Feed').'</a>
                </p>';
}

echo '
            </div>
            <div style="clear:both"></div>

        </div><!-- #centercontent -->';

// Show Footer
include_once(getTheme($current_user_id) . 'footer.php'); ?>
