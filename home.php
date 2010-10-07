<?php
/**
 * Home
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
if (!isset($_SESSION)) {
    session_start();
}

require_once 'inc/config_inc.php';
require_once 'inc/util_inc.php';
require_once 'inc/locale.php';
require_once 'inc/calendar_class.php';
require_once 'inc/poll_class.php';
require_once 'inc/database_class.php';
require_once 'inc/alerts_class.php';

fixMagicQuotes();

// Check that the user is logged in
isLoggedIn();
$currentUserId = cleanInput($_SESSION['login_id'], 'int');

// Update activity
mysql_query("UPDATE `fcms_users` SET `activity`=NOW() WHERE `id` = '$currentUserId'");

$locale     = new Locale();
$calendar   = new Calendar($currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$poll       = new Poll($currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$database   = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
$alert      = new Alerts($currentUserId, $database);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Home'),
    'path'          => "",
    'admin_path'    => "admin/",
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
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
$theme = getTheme($currentUserId);
require_once $theme.'header.php';

echo '
        <div id="home" class="centercontent">

            <div id="leftcolumn">
                <h2 class="calmenu">'.T_('Calendar').'</h2>';

// Use the supplied date, if available
if (isset($_GET['year']) && isset($_GET['month']) && isset($_GET['day'])) {
    $year  = cleanInput($_GET['year'], 'int');
    $month = cleanInput($_GET['month'], 'int');
    $month = str_pad($month, 2, 0, STR_PAD_LEFT);
    $day   = cleanInput($_GET['day'], 'int');
    $day   = str_pad($day, 2, 0, STR_PAD_LEFT);
// get today's date
} else {
    $year  = $locale->fixDate('Y', $calendar->tz_offset, gmdate('Y-m-d H:i:s'));
    $month = $locale->fixDate('m', $calendar->tz_offset, gmdate('Y-m-d H:i:s'));
    $day   = $locale->fixDate('d', $calendar->tz_offset, gmdate('Y-m-d H:i:s'));
}

// Display Small Calendar
$calendar->displaySmallCalendar($month, $year, $day);

// Display This months events
$calendar->displayMonthEvents($month, $year);

// Display next months events
$currentMonth = gmdate('Y-m-d H:i:s', gmmktime(gmdate('h'), gmdate('i'), gmdate('s'), $month+1, 1, $year));
$nextMonth    = $locale->fixDate('m', $calendar->tz_offset, $currentMonth);
$calendar->displayMonthEvents($nextMonth, $year);

// TODO
// Create a function canDisplayPoll() for this

// Display poll
//  1. not voting                       - no POST[vote] or no POST[option_id]
//  2. not looking at prev poll results - no GET[poll_id]
//  3. not looking at prev poll list    - no GET[action]
if (   (!isset($_POST['vote']) || !isset($_POST['option_id']))
    && !isset($_GET['poll_id'])
    && !isset($_GET['action'])
) {
    $poll->displayPoll('0', false);
}
echo '
                <h2 class="membermenu">'.T_('Members Online').'</h2>
                <div class="membermenu">';
displayMembersOnline();
echo '
                </div>
            </div>

            <div id="maincolumn">';
$showWhatsNew = true;

// Display Poll
if (isset($_GET['poll_id']) && !isset($_GET['action']) && !isset($_POST['vote'])) {
    $showWhatsNew = false;
    $id = cleanInput($_GET['poll_id'], 'int');
    $poll->displayPoll($id);
}
// Vote on Poll
if (isset($_POST['vote']) and isset($_POST['option_id'])) {
    $showWhatsNew = false;
    $option = cleanInput($_POST['option_id'], 'int');
    if (isset($_GET['poll_id'])) {
        $id = cleanInput($_GET['poll_id'], 'int');
    } else {
        $id = cleanInput($_POST['poll_id'], 'int');
    }
    $poll->placeVote($currentUserId, $option, $id);
    $poll->displayResults($id);
}
// Display Poll Results
if (isset($_GET['action'])) {
    $showWhatsNew = false;
    if ($_GET['action'] == "results") {
        if (isset($_GET['poll_id'])) {
            $id = cleanInput($_GET['poll_id'], 'int');
        } else {
            $id = cleanInput($_POST['poll_id'], 'int');
        }
        $poll->displayResults($id);
    } elseif ($_GET['action'] == "pastpolls") {
        $page = 1;
        if (isset($_GET['page'])) {
            $page = cleanInput($_GET['page'], 'int');
        }
        $poll->displayPastPolls($page);
    }
}

// If were not showing poll stuff, show the latest info
if ($showWhatsNew) {

    // Remove an alert
    if (isset($_GET['alert'])) {
        $sql = "INSERT INTO `fcms_alerts` (`alert`, `user`)
                VALUES (
                    '" . cleanInput($_GET['alert']) . "', 
                    '$currentUserId'
                )";
        mysql_query($sql) or displaySQLError(
            'Remove Alert Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
    }

    // Show Alerts
    $alert->displayNewAdminHome($currentUserId);
    $alert->displayNewUserHome($currentUserId);

    // Show any events happening today
        // Note: no need to fix dates for locale
        //       the db stores dates in server tz
    list($db_year, $db_month, $db_day) = explode('-', date('Y-m-d'));
    $calendar->displayTodaysEvents($db_month, $db_day, $db_year);

    // Show what's new based on user's settings
    echo '
                <h2>'.T_('What\'s New').'</h2>';
    $sql = "SELECT `frontpage` 
            FROM `fcms_user_settings` 
            WHERE `user` = '$currentUserId'";
    $result = mysql_query($sql) or displaySQLError(
        'Frontpage Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    $r = mysql_fetch_array($result);
    mysql_free_result($result);

    // All by date
    if ($r['frontpage'] < 2) {
        displayWhatsNewAll($currentUserId);

    // Last 5 by category
    } else {
        require_once 'inc/messageboard_class.php';
        require_once 'inc/addressbook_class.php';
        require_once 'inc/familynews_class.php';
        require_once 'inc/gallery_class.php';
        require_once 'inc/prayers_class.php';
        require_once 'inc/recipes_class.php';
        require_once 'inc/documents_class.php';
        require_once 'inc/database_class.php';

        $database = new database('mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $mboard = new MessageBoard($currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $book = new AddressBook($currentUserId, $database);
        $news = new FamilyNews($currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $gallery = new PhotoGallery($currentUserId, $database);
        $prayers = new Prayers($currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $recs = new Recipes($currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        $docs = new Documents($currentUserId, 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
        echo '
                <div class="half">';
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
                    <h3>'.T_('Comments').'</h3>
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

        // Display Latest Comments
        if (mysql_num_rows($result) > 0) {

            $today_start = $locale->fixDate('Ymd', $mboard->tz_offset, gmdate('Y-m-d H:i:s')) . '000000';
            $today_end = $locale->fixDate('Ymd', $mboard->tz_offset, gmdate('Y-m-d H:i:s')) . '235959';

            while ($com = mysql_fetch_array($result)) {
                $date = $locale->fixDate('YmdHis', $mboard->tz_offset, $com['date']);
                $comment = $com['comment'];
                if (strlen($comment) > 30) {
                    $comment = substr($comment, 0, 27) . "...";
                }
                if ($date >= $today_start && $date <= $today_end) {
                    $full_date = T_('Today');
                    $d = ' class="today"';
                } else {
                    $full_date = $locale->fixDate(T_('M. j, Y, g:i a'), $mboard->tz_offset, $com['date']);
                    $d = '';
                }
                if ($com['check'] !== 'NEWS') {
                    $url = 'gallery/index.php?uid=0&amp;cid=comments&amp;pid='.$com['id2'];
                } else {
                    $url = 'familynews.php?getnews='.$com['id'].'&amp;newsid='.$com['id2'];
                }
                $user = cleanOutput($com['user']);
                $userName = getUserDisplayName($com['user']);
                echo '
                        <li>
                            <div'.$d.'>'.$full_date.'</div>
                            <a href="'.$url.'">'.$comment.'</a> - <a href="profile.php?member='.$user.'" class="u">'.$userName.'</a>
                        </li>';
            }
        } else {
            echo '
                        <li><i>'.T_('nothing new last 30 days').'</i></li>';
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
                    <a class="rss" href="rss.php?feed=all">'.T_('RSS Feed').'</a>
                </p>';
}

echo '
            </div>
            <div style="clear:both"></div>

        </div><!-- #centercontent -->';

// Show Footer
require_once $theme.'footer.php';
