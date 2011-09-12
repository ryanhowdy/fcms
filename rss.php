<?php
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');

fixMagicQuotes();

if (isset($_GET['feed'])) {
    if ($_GET['feed'] == 'all') {
        header("Content-Type: application/rss+xml");
        displayFeedAll();
    } elseif ($_GET['feed'] == 'gallery') {
        header("Content-Type: application/rss+xml");
        displayFeedPhotoGallery();
    } else {
        echo "<p>".T_('The RSS feed you requested doesn\'t exist')."</p>";
    }
} else {
    header("Content-Type: application/rss+xml");
    displayFeedAll();
}

/**
 * displayFeedAll 
 *
 * Displays RSS 2.0 feed for all updates to the site
 * 
 * @author: choc
 * @author: Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @return  void
 */
function displayFeedAll ()
{
    $url     = "http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']; 
    $urlRoot = $url; 

    $pos = strrpos($url, "/"); 
    if ($pos===false)
    { 
        $pos = strrpos($url, "\\"); 
    } 
    if (!($pos===false))
    { 
        $urlRoot = substr($url, 0, $pos); 
    } 

    // Get data
    $whatsNewData = getWhatsNewData(1, 30); // Use userid 1
    if ($whatsNewData === false)
    {
        return;
    }

    $output = "<?xml version=\"1.0\"?" . "> 
    <rss version=\"2.0\"> 
    <channel> 
    <title>" . getSiteName() . " - " . T_('What\'s New') . "</title> 
    <link>" . $url . "</link> 
    <description>" . getSiteName() . " - " . T_('What\'s New') . " " . T_('RSS Feed') . "</description> 
    <language>" . T_('lang') . "</language> 
    <managingEditor>" . getContactEmail() . "</managingEditor> 
    "; 
    
    foreach ($whatsNewData as $line)
    { 
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
                $title = sprintf(T_('%s started the new thread %s.'), $userName, $subject);
            } else {
                $title = sprintf(T_('%s replied to %s.'), $userName, $subject);
            }

        // New Members
        } elseif ($line['type'] == 'JOINED') { 
            $displayname = getUserDisplayName($line['userid']);
            $link = "profile.php?member=" . $line['userid']; 
            $title = sprintf(T_('%s has joined the website.'), $displayname);

        // Edit Address
        } elseif ($line['type'] == 'ADDRESSEDIT') {
            $displayname = getUserDisplayName($line['id2']);
            $link = 'addressbook.php?address='.$line['id'];
            $title = sprintf(T_('%s has updated his/her address.'), $displayname);

        // Add Address
        } elseif ($line['type'] == 'ADDRESSADD') {
            $displayname = getUserDisplayName($line['id2']);
            $for = getUserDisplayName($line['userid'], 2, false);
            $link = 'addressbook.php?address='.$line['id'];
            $title = sprintf(T_('%s has added address information for %s.'), $displayname, $for);

        // Family News
        } elseif ($line['type'] == 'NEWS') { 
            $displayname = getUserDisplayName($line['userid']);
            $link = 'familynews.php?getnews='.$line['userid'].'&amp;newsid='.$line['id']; 
            $title = sprintf(T_('%s has added %s to his/her Family News.'), $displayname, $line['title']);

        // Prayer Concerns
        } elseif ($line['type'] == 'PRAYERS') { 
            $displayname = getUserDisplayName($line['userid']);
            $link = 'prayers.php';
            $title = sprintf(T_('%s has added a Prayer Concern for %s.'), $displayname, $line['title']);

        // Recipes
        } elseif ($line['type'] == 'RECIPES') { 
            $displayname = getUserDisplayName($line['userid']);
            switch ($line['id2']) { 
                case T_('Appetizer'): $link = "recipes.php?category=1&amp;id=" . $line['id']; break; 
                case T_('Breakfast'): $link = "recipes.php?category=2&amp;id=" . $line['id']; break; 
                case T_('Dessert'): $link = "recipes.php?category=3&amp;id=" . $line['id']; break; 
                case T_('Entree (Meat)'): $link = "recipes.php?category=4&amp;id=" . $line['id']; break; 
                case T_('Entree (Seafood)'): $link = "recipes.php?category=5&amp;id=" . $line['id']; break; 
                case T_('Entree (Vegetarian)'): $link = "recipes.php?category=6&amp;id=" . $line['id']; break; 
                case T_('Salad'): $link = "recipes.php?category=7&amp;id=" . $line['id']; break; 
                case T_('Side Dish'): $link = "recipes.php?category=8&amp;id=" . $line['id']; break; 
                case T_('Soup'): $link = "recipes.php?category=9&amp;id=" . $line['id']; break; 
                default: $link = "recipes.php"; break; 
            } 
            $title = sprintf(T_('%s has added the %s recipe.'), $displayname, $line['title']);

        // Documents
        } elseif ($line['type'] == 'DOCS') {
            $displayname = getUserDisplayName($line['userid']);
            $link = 'documents.php';
            $title = sprintf(T_('%s has added a new Document (%s).'), $displayname, $line['title']);

        // Photo Gallery
        } elseif ($line['type'] == 'GALLERY') { 
            $displayname = getUserDisplayName($line['userid']);
            $link = 'gallery/index.php?uid='.$line['userid'].'&amp;cid='.$line['id'];
            $title = sprintf(T_('%s has added %d new photos to the %s category.'), $displayname, $line['id2'], $line['title']);

        // Comment - Family News
        } elseif ($line['type'] == 'NEWSCOM') { 
            $displayname = getUserDisplayName($line['userid']);
            $link = 'familynews.php?getnews='.$line['userid'].'&amp;newsid='.$line['id'];
            $title = sprintf(T_('%s commented on Family News %s.'), $displayname, $line['title']);

        // Comment - Photo Gallery
        } elseif ($line['type'] == 'GALCOM') { 
            $displayname = getUserDisplayName($line['userid']);
            $link = 'gallery/index.php?uid=0&amp;cid=comments&amp;pid='.$line['id'];
            $title = sprintf(T_('%s commented on the following photo:'), $displayname);

        // Calendar
        } elseif ($line['type'] == 'CALENDAR') {
            // TODO
            // copy from calendar_class
            $date_date = gmdate(T_('m-d-y'), strtotime($line['id2']));
            $date_date2 = gmdate(T_('F j, Y'), strtotime($line['id2']));
            $displayname = getUserDisplayName($line['userid']);
            $link = 'calendar.php?year='.gmdate('Y', strtotime($date_date2))
                .'&amp;month='.gmdate('m', strtotime($date_date2))
                .'&amp;day='.gmdate('d', strtotime($date_date2));
            $title = sprintf(T_('%s has added a new Calendar entry on %s for %s.'), $displayname, $date_date, $line['title']);

        // Poll
        } elseif ($line['type'] == 'POLL') {
            $link = 'home.php?poll_id='.$line['id'];
            $title = sprintf(T_('A new Poll (%s) has been added.'), $line['title']);
        }
        
        $output .= "<item><title><![CDATA[$title]]></title> 
        <pubDate>" . gmdate(T_('D, d M Y H:i:s'), strtotime($line['date'])) . " GMT</pubDate> 
        <link>$urlRoot/$link</link> 
        <guid isPermaLink=\"false\"><![CDATA[$urlRoot $title " . gmdate(T_('D, d M Y H:i:s'), strtotime($line['date'])) . "]]></guid> 
        </item> 
        "; 
    } 
    
    $output .= "</channel></rss>"; 
    echo $output;
}

/**
 * displayFeedPhotoGallery 
 * 
 * @return void
 */
function displayFeedPhotoGallery () 
{
    $url     = "http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']; 
    $urlroot = $url; 

    $pos = strrpos($url, "/"); 
    if ($pos===false)
    { 
        $pos = strrpos($url, "\\"); 
    } 

    if (!($pos===false))
    {
        $urlroot = substr($url, 0, $pos); 
    } 

    $lastday = time() - (84 * 60 * 60 * 24); // 12 weeks 

    $sql = "SELECT `caption`, p.`user`, `filename`, p.`date`, `name` 
            FROM `fcms_gallery_photos` AS p, `fcms_category` As c
            WHERE p.`category` = c.`id` 
            AND UNIX_TIMESTAMP(p.`date`) >= $lastday 
            ORDER BY p.`date`"; 
    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Photo Gallery Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        return;
    }

    $return = array();

    while ($line = mysql_fetch_assoc($result))
    { 
        $return[] = $line; 
    }

    $output = "<?xml version=\"1.0\"?" . "> 
    <rss version=\"2.0\"> 
    <channel> 
    <title>" . getSiteName() . " - " . T_('Photo Gallery') . "</title> 
    <link>" . $url . "</link> 
    <description>" . getSiteName() . " - " . T_('Photo Gallery') . " " . T_('RSS Feed') . "</description> 
    <language>" . T_('lang') . "</language> 
    <managingEditor>" . getContactEmail() . "</managingEditor> 
    ";

    if (count($return) > 0)
    {
        foreach ($return as $line)
        {
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
    }
    $output .= "</channel></rss>"; 
    echo $output; 
}

/**
 * returnMIMEType 
 * 
 * @param string $filename 
 * @return void
 */
function returnMIMEType ($filename) 
{ 
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
