<?php
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
if (isset($_GET['feed'])) {
    if ($_GET['feed'] == 'all') {
        header("Content-Type: application/rss+xml");
        displayFeedAll();
    } elseif ($_GET['feed'] == 'gallery') {
        header("Content-Type: application/rss+xml");
        displayFeedPhotoGallery();
    } else {
        echo "<p>"._('The RSS feed you requested doesn\'t exist')."</p>";
    }
} else {
    header("Content-Type: application/rss+xml");
    displayFeedAll();
}

/*
 * Function: displayFeedAll
 * Written by: choc
 * Updated by: Ryan Haudenschilt <r_haudenschilt@hotmail.com>
 *
 * Displays RSS 2.0 feed for all updates to the site
 */
function displayFeedAll () {
    $url = "http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']; 
    $urlRoot = $url; 
    $pos = strrpos($url, "/"); 
    if ($pos===false) { 
        $pos = strrpos($url, "\\"); 
    } 
    if (!($pos===false)) { 
        $urlRoot = substr($url, 0, $pos); 
    } 
    $lastday = time() - (84 * 60 * 60 * 24); // 12 weeks 
    $sql = "SELECT p.`id`, `date`, `subject` AS title, u.`id` AS userid, `thread` AS id2, 0 AS id3, 'BOARD' AS type 
            FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t, fcms_users AS u 
            WHERE p.`thread` = t.`id` 
            AND p.`user` = u.`id` 
            AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) 

            UNION SELECT a.id, a.updated AS date, 0 AS title, a.user AS userid, a.entered_by AS id2, u.joindate AS id3, 'ADDRESSEDIT' AS type
            FROM fcms_address AS a, fcms_users AS u
            WHERE a.user = u.id
            AND DATE_FORMAT(a.updated, '%Y-%m-%d %h') != DATE_FORMAT(u.joindate, '%Y-%m-%d %h') 
            AND a.updated >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) 

            UNION SELECT a.id, a.updated AS date, 0 AS title, a.user AS userid, a.entered_by AS id2, u.joindate AS id3, 'ADDRESSADD' AS type
            FROM fcms_address AS a, fcms_users AS u
            WHERE a.user = u.id
            AND u.`password` = 'NONMEMBER' 
            AND a.updated >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) 

            UNION SELECT `id`, `joindate` AS date, 0 AS title, `id` AS userid, 0 AS id2, 0 AS id3, 'JOINED' AS type 
            FROM `fcms_users` 
            WHERE `password` != 'NONMEMBER' 
            AND `activated` > 0 
            AND `joindate` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
    if (usingFamilyNews()) {
        $sql .= "UNION SELECT n.`id` AS id, n.`date`, `title`, u.`id` AS userid, 0 AS id2, 0 AS id3, 'NEWS' AS type 
                 FROM `fcms_users` AS u, `fcms_news` AS n 
                 WHERE u.`id` = n.`user` 
                 AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) 
                 AND `username` != 'SITENEWS' 
                 AND `password` != 'SITENEWS' ";
    }
    if (usingPrayers()) {
        $sql .= "UNION SELECT 0 AS id, `date`, `for` AS title, `user` AS userid, 0 AS id2, 0 AS id3, 'PRAYERS' AS type 
                 FROM `fcms_prayers` 
                 WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
    }
    if (usingRecipes()) {
        $sql .= "UNION SELECT `id` AS id, `date`, `name` AS title, `user` AS userid, `category` AS id2, 0 AS id3, 'RECIPES' AS type 
                 FROM `fcms_recipes` 
                 WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
    }
    if (usingdocuments()) {
        $sql .= "UNION SELECT d.`id` AS 'id', d.`date`, `name` AS title, d.`user` AS userid, 0 AS id2, 0 AS id3, 'DOCS' AS type 
                 FROM `fcms_documents` AS d, `fcms_users` AS u 
                 WHERE d.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                 AND d.`user` = u.`id` ";
    }
    $sql .= "UNION SELECT DISTINCT p.`category` AS id, `date`, `name` AS title, p.`user` AS userid, COUNT(*) AS id2, DAYOFYEAR(`date`) AS id3, 'GALLERY' AS type 
             FROM `fcms_gallery_photos` AS p, `fcms_users` AS u, `fcms_gallery_category` AS c 
             WHERE p.`user` = u.`id` 
             AND p.`category` = c.`id` 
             AND 'date' >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
             GROUP BY userid, title, id3 ";
    if (usingFamilyNews()) {
        $sql .= "UNION SELECT n.`id` AS 'id', nc.`date`, `title`, nc.`user` AS userid, 0 AS id2, 0 AS id3, 'NEWSCOM' AS type 
                 FROM `fcms_news_comments` AS nc, `fcms_news` AS n, `fcms_users` AS u 
                 WHERE nc.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                 AND nc.`user` = u.`id` 
                 AND n.`id` = nc.`news` ";
    }
    $sql .= "UNION SELECT p.`id`, gc.`date`, `comment` AS title, gc.`user` AS userid, p.`user` AS id2, `filename` AS id3, 'GALCOM' AS type 
             FROM `fcms_gallery_comments` AS gc, `fcms_users` AS u, `fcms_gallery_photos` AS p 
             WHERE gc.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
             AND gc.`user` = u.`id` 
             AND gc.`photo` = p.`id` 

             UNION SELECT c.`id`, c.`date_added` AS date, `title`, `created_by` AS userid, `date` AS id2, `type` AS id3, 'CALENDAR' AS type 
             FROM `fcms_calendar` AS c, `fcms_users` AS u 
             WHERE c.`date_added` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
             AND c.`created_by` = u.`id` AND `private` < 1 

             UNION SELECT `id`, `started` AS date, `question`, '0' AS userid, 'na' AS id2, 'na' AS id3, 'POLL' AS type 
             FROM `fcms_polls` 
             WHERE `started` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 

             ORDER BY date DESC LIMIT 0, 35";
    $result = mysql_query($sql) or displaySQLError(
        'Latest Info Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
    );
    while ($line = mysql_fetch_assoc($result)) { 
        $return[] = $line; 
    } 
    $output = "<?xml version=\"1.0\"?" . "> 
    <rss version=\"2.0\"> 
    <channel> 
    <title>" . getSiteName() . " - " . _('What\'s New') . "</title> 
    <link>" . $url . "</link> 
    <description>" . getSiteName() . " - " . _('What\'s New') . " " . _('RSS Feed') . "</description> 
    <language>" . _('lang') . "</language> 
    <managingEditor>" . getContactEmail() . "</managingEditor> 
    "; 
    
    foreach ($return as $line) { 
        $title = ""; 
        $link = ""; 
        $guid = ""; 
        
        // Message Board
        if ($line['type'] == 'BOARD') {
            $check = mysql_query("SELECT min(`id`) AS id FROM `fcms_board_posts` WHERE `thread` = " . $line['id2']) or die("<h1>Thread or Post Error (util.inc.php 360)</h1>" . mysql_error());
            $minpost = mysql_fetch_array($check);
            $userName = getUserDisplayName($line['userid']);
            $subject = $line['title'];
            $pos = strpos($subject, '#ANOUNCE#');
            if ($pos !== false) {
                $subject = substr($subject, 9, strlen($subject)-9);
            }
            $link = "messageboard.php?thread=".$line['id2'];
            if ($line['id'] == $minpost['id']) {
                $title = sprintf(_('%s started the new thread %s.'), $userName, $subject);
            } else {
                $title = sprintf(_('%s replied to %s.'), $userName, $subject);
            }

        // New Members
        } elseif ($line['type'] == 'JOINED') { 
            $displayname = getUserDisplayName($line['userid']);
            $link = "profile.php?member=" . $line['userid']; 
            $title = sprintf(_('%s has joined the website.'), $displayname);

        // Edit Address
        } elseif ($line['type'] == 'ADDRESSEDIT') {
            $displayname = getUserDisplayName($line['id2']);
            $link = 'addressbook.php?address='.$line['id'];
            $title = sprintf(_('%s has updated his/her address.'), $displayname);

        // Add Address
        } elseif ($line['type'] == 'ADDRESSADD') {
            $displayname = getUserDisplayName($line['id2']);
            $for = getUserDisplayName($line['userid'], 2, false);
            $link = 'addressbook.php?address='.$line['id'];
            $title = sprintf(_('%s has added address information for %s.'), $displayname, $for);

        // Family News
        } elseif ($line['type'] == 'NEWS') { 
            $displayname = getUserDisplayName($line['userid']);
            $link = 'familynews.php?getnews='.$line['userid'].'&amp;newsid='.$line['id']; 
            $title = sprintf(_('%s has added %s to his/her Family News.'), $displayname, $line['title']);

        // Prayer Concerns
        } elseif ($line['type'] == 'PRAYERS') { 
            $displayname = getUserDisplayName($line['userid']);
            $link = 'prayers.php';
            $title = sprintf(_('%s has added a Prayer Concern for %s.'), $displayname, $line['title']);

        // Recipes
        } elseif ($line['type'] == 'RECIPES') { 
            $displayname = getUserDisplayName($line['userid']);
            switch ($line['id2']) { 
                case _('Appetizer'): $link = "recipes.php?category=1&amp;id=" . $line['id']; break; 
                case _('Breakfast'): $link = "recipes.php?category=2&amp;id=" . $line['id']; break; 
                case _('Dessert'): $link = "recipes.php?category=3&amp;id=" . $line['id']; break; 
                case _('Entree (Meat)'): $link = "recipes.php?category=4&amp;id=" . $line['id']; break; 
                case _('Entree (Seafood)'): $link = "recipes.php?category=5&amp;id=" . $line['id']; break; 
                case _('Entree (Vegetarian)'): $link = "recipes.php?category=6&amp;id=" . $line['id']; break; 
                case _('Salad'): $link = "recipes.php?category=7&amp;id=" . $line['id']; break; 
                case _('Side Dish'): $link = "recipes.php?category=8&amp;id=" . $line['id']; break; 
                case _('Soup'): $link = "recipes.php?category=9&amp;id=" . $line['id']; break; 
                default: $link = "recipes.php"; break; 
            } 
            $title = sprintf(_('%s has added the %s recipe.'), $displayname, $line['title']);

        // Documents
        } elseif ($line['type'] == 'DOCS') {
            $displayname = getUserDisplayName($line['userid']);
            $link = 'documents.php';
            $title = sprintf(_('%s has added a new Document (%s).'), $displayname, $line['title']);

        // Photo Gallery
        } elseif ($line['type'] == 'GALLERY') { 
            $displayname = getUserDisplayName($line['userid']);
            $link = 'gallery/index.php?uid='.$line['userid'].'&amp;cid='.$line['id'];
            $title = sprintf(_('%s has added %d new photos to the %s category.'), $displayname, $line['id2'], $line['title']);

        // Comment - Family News
        } elseif ($line['type'] == 'NEWSCOM') { 
            $displayname = getUserDisplayName($line['userid']);
            $link = 'familynews.php?getnews='.$line['userid'].'&amp;newsid='.$line['id'];
            $title = sprintf(_('%s commented on Family News %s.'), $displayname, $line['title']);

        // Comment - Photo Gallery
        } elseif ($line['type'] == 'GALCOM') { 
            $displayname = getUserDisplayName($line['userid']);
            $link = 'gallery/index.php?uid=0&amp;cid=comments&amp;pid='.$line['id'];
            $title = sprintf(_('%s commented on the following photo:'), $displayname);

        // Calendar
        } elseif ($line['type'] == 'CALENDAR') {
            $date_date = gmdate(_('m-d-y'), strtotime($line['id2']));
            $date_date2 = gmdate(_('F j, Y'), strtotime($line['id2']));
            $displayname = getUserDisplayName($line['userid']);
            $link = 'calendar.php?year='.date('Y', strtotime($date_date2))
                .'&amp;month='.date('m', strtotime($date_date2))
                .'&amp;day='.date('d', strtotime($date_date2));
            $title = sprintf(_('%s has added a new Calendar entry on %s for %s.'), $displayname, $date_date, $line['title']);

        // Poll
        } elseif ($line['type'] == 'POLL') {
            $link = 'home.php?poll_id='.$line['id'];
            $title = sprintf(_('A new Poll (%s) has been added.'), $line['title']);
        }
        
        $output .= "<item><title><![CDATA[$title]]></title> 
        <pubDate>" . gmdate(_('D, d M Y H:i:s'), strtotime($line['date'])) . " GMT</pubDate> 
        <link>$urlRoot/$link</link> 
        <guid isPermaLink=\"false\"><![CDATA[$urlRoot $title " . gmdate(_('D, d M Y H:i:s'), strtotime($line['date'])) . "]]></guid> 
        </item> 
        "; 
    } 
    
    $output .= "</channel></rss>"; 
    echo $output;
}

function displayFeedPhotoGallery () {
    $url = "http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']; 
    $urlroot = $url; 
    $pos = strrpos($url, "/"); 
    if ($pos===false) { 
        $pos = strrpos($url, "\\"); 
    } 
    if (!($pos===false)) { 
        $urlroot = substr($url, 0, $pos); 
    } 
    $lastday = time() - (84 * 60 * 60 * 24); // 12 weeks 
    $sql = "SELECT caption, fcms_gallery_photos.user, filename, date, name FROM `fcms_gallery_photos`, `fcms_gallery_category` WHERE fcms_gallery_photos.category = fcms_gallery_category.id AND UNIX_TIMESTAMP(date) >= $lastday ORDER BY `date` "; 
    $result = mysql_query($sql) or displaySQLError('Latest Photo Gallery Error', 'rss.php [' . __LINE__ . ']', $sql, mysql_error());
    while ($line = mysql_fetch_assoc($result)) { 
        $return[] = $line; 
    } 

    $output = "<?xml version=\"1.0\"?" . "> 
    <rss version=\"2.0\"> 
    <channel> 
    <title>" . getSiteName() . " - " . _('Photo Gallery') . "</title> 
    <link>" . $url . "</link> 
    <description>" . getSiteName() . " - " . _('Photo Gallery') . " " . _('RSS Feed') . "</description> 
    <language>" . _('lang') . "</language> 
    <managingEditor>" . getContactEmail() . "</managingEditor> 
    "; 
    
    foreach ($return as $line) {
        $title = htmlentities($line['caption']);
        if ($title == "") {
            $title = htmlentities($line['name']);
        }
        $output .= "<item><title><![CDATA[$title]]></title> 
        <pubDate>" . gmdate('D, d M Y H:i:s', strtotime($line['date'])) . " GMT</pubDate> 
        <link>".htmlentities( $urlroot."/gallery/photos/member".$line['user']."/".$line['filename'])."</link>              
        <description><![CDATA[<img src=\"$urlroot/gallery/photos/member".$line['user']."/".$line['filename']."\" border=\"0\" />]]></description> 
        <enclosure url=\"".$urlroot."/gallery/photos/member".$line['user']."/".$line['filename']."\" type=\"". returnMIMEType("./gallery/photos/member".$line['user']."/".$line['filename']) ."\" length=\"". filesize("./gallery/photos/member".$line['user']."/".$line['filename'])."\" /> 
        <guid isPermaLink=\"true\"><![CDATA[".$urlroot."/gallery/photos/member".$line['user']."/".$line['filename']."]]></guid> 
        </item> 
        "; 
    } 
    $output .= "</channel></rss>"; 
    echo $output; 
}

function returnMIMEType($filename) { 
    preg_match("|\.([a-z0-9]{2,4})$|i", $filename, $fileSuffix); 
    switch(strtolower($fileSuffix[1])) { 
        case "jpg":
        case "jpeg":
        case "jpe":
            return "image/jpg";
            break;
        case "png": 
        case "gif": 
        case "bmp": 
        case "tiff": 
            return "image/".strtolower($fileSuffix[1]); 
            break;
        case "html": 
        case "htm": 
        case "php": 
            return "text/html"; 
            break;
        case "txt" : 
            return "text/plain"; 
            break;
        case "mpeg": 
        case "mpg": 
        case "mpe": 
            return "video/mpeg"; 
            break;
        case "mp3": 
            return "audio/mpeg3"; 
            break;
        case "wav": 
            return "audio/wav"; 
            break;
        case "aiff": 
        case "aif": 
            return "audio/aiff"; 
            break;
        case "avi": 
            return "video/msvideo"; 
            break;
        case "wmv": 
            return "video/x-ms-wmv"; 
            break;
        case "mov" : 
            return "video/quicktime"; 
            break;
        default : 
            if (function_exists("mime_content_type")) { 
                $fileSuffix = mime_content_type($filename); 
            } 
            return "unknown/" . trim($fileSuffix[0], "."); 
            break;
    } 
} ?>