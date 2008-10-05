<?php
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/language.php');
if (isset($_GET['feed'])) {
	if ($_GET['feed'] == 'all') {
		header("Content-Type: application/rss+xml");
		displayFeedAll();
	} elseif ($_GET['feed'] == 'gallery') {
		header("Content-Type: application/rss+xml");
		displayFeedPhotoGallery();
	} else {
		echo "<p>" . $LANG['no_rss'] . "</p>";
	}
} else {
	echo "<p>You should not be here.</p>";
}

/*
 * Function: displayFeedAll
 * Written by: choc
 * Updated by: Ryan Haudenschilt <r_haudenschilt@hotmail.com>
 *
 * Displays RSS 2.0 feed for all updates to the site
 */
function displayFeedAll () {
	global $LANG;
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
	$sql = "SELECT p.`id`, `date`, `subject` AS title, u.`id` AS userid, `thread` AS id2, 0 AS id3, 'BOARD' AS type FROM `fcms_board_posts` AS p, `fcms_board_threads` AS t, fcms_users AS u WHERE p.`thread` = t.`id` AND p.`user` = u.`id` AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) " 
		. "UNION SELECT a.`id`, `updated` AS 'date', 0 AS title, `user` AS userid, `entered_by` AS id2, 0 AS id3, 'ADDRESS' AS type FROM `fcms_users` AS u, `fcms_address` AS a WHERE u.`id` = a.`user` AND 'date' >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) "; 
	if (usingFamilyNews()) { $sql .= "UNION SELECT n.`id` AS id, n.`date`, `title`, u.`id` AS userid, 0 AS id2, 0 AS id3, 'NEWS' AS type FROM `fcms_users` AS u, `fcms_news` AS n WHERE u.`id` = n.`user` AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND `username` != 'SITENEWS' AND `password` != 'SITENEWS' "; } 
	if (usingPrayers()) { $sql .= "UNION SELECT 0 AS id, `date`, `for` AS title, `user` AS userid, 0 AS id2, 0 AS id3, 'PRAYERS' AS type FROM `fcms_prayers` WHERE `date` >= DATE_SUB(CURDATE() , INTERVAL 30 DAY) "; } 
	if (usingRecipes()) { $sql .= "UNION SELECT `id` AS id, `date`, `name` AS title, `user` AS userid, `category` AS id2, 0 AS id3, 'RECIPES' AS type FROM `fcms_recipes` WHERE `date` >= DATE_SUB(CURDATE() , INTERVAL 30 DAY) "; } 
	$sql .= "UNION SELECT DISTINCT p.`category` AS id, `date`, `name` AS title, p.`user` AS userid, COUNT(*) AS id2, DAYOFYEAR(`date`) AS id3, 'GALLERY' AS type FROM `fcms_gallery_photos` AS p, `fcms_users` AS u, `fcms_gallery_category` AS c WHERE p.`user` = u.`id` AND p.`category` = c.`id` AND 'date' >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) GROUP BY userid, title, id3 "; 
	if (usingFamilyNews()) { $sql .= "UNION SELECT n.`id` AS 'id', nc.`date`, `title`, nc.`user` AS userid, nc.`id` AS id2, 0 AS id3, 'NEWSCOM' AS type FROM `fcms_news_comments` AS nc, `fcms_news` AS n, `fcms_users` AS u WHERE nc.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)  AND nc.`user` = u.`id` AND n.`id` = nc.`news` "; } 
	$sql .= "UNION SELECT p.`id`, gc.`date`, `comment` AS title, gc.`user` AS userid, p.`user` AS id2, `filename` AS id3, 'GALCOM' AS type FROM `fcms_gallery_comments` AS gc, `fcms_users` AS u, `fcms_gallery_photos` AS p WHERE gc.`date` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND gc.`user` = u.`id` AND gc.`photo` = p.`id` "; 
	$sql .= "ORDER BY date DESC LIMIT 0, 35";
	$result = mysql_query($sql) or displaySQLError('Latest Info Error', 'rss.php [' . __LINE__ . ']', $sql, mysql_error());
	while ($line = mysql_fetch_assoc($result)) { 
		$return[] = $line; 
	} 
	$output = "<?xml version=\"1.0\"?" . "> 
	<rss version=\"2.0\"> 
	<channel> 
	<title>" . getSiteName() . " - " . $LANG['whats_new'] . "</title> 
	<link>" . $url . "</link> 
	<description>" . getSiteName() . " - " . $LANG['whats_new'] . " " . $LANG['rss_feed'] . "</description> 
	<language>" . $LANG['lang'] . "</language> 
	<managingEditor>" . getContactEmail() . "</managingEditor> 
	"; 
	
	foreach ($return as $line) { 
		$title = ""; 
		$link = ""; 
		$guid = ""; 
		
		if ($line['type'] == 'BOARD') {
			$sql = "SELECT MIN(`id`) AS id FROM `fcms_board_posts` WHERE `thread` = " . $line['id2'];
			$check = mysql_query($sql) or displaySQLError('Error', 'rss.php [' . __LINE__ . ']', $sql, mysql_error()); 
			$minpost = mysql_fetch_array($check); 
			$subject = $line['title']; 
			$pos = strpos($subject, '#ANOUNCE#'); 
			if ($pos !== false) { $subject = substr($subject, 9, strlen($subject)-9); } 
			if ($line['id'] == $minpost['id']) { 
				$link = "messageboard.php?thread=" . $line['id2']; 
				$title = getUserDisplayName($line['userid']) . " " . $LANG['started_thread'] . " '$subject'"; 
			} else { 
				$link = "messageboard.php?thread=" . $line['id2']; 
				$title = getUserDisplayName($line['userid']) . " " . $LANG['replied_to'] . " '$subject'"; 
			} 
		} elseif ($line['type'] == 'ADDRESS') { 
			$sql = "SELECT `joindate`, `password` FROM `fcms_users` WHERE `id` = " . $line['userid'];
			$new_result = mysql_query($sql) or displaySQLError('Error', 'rss.php [' . __LINE__ . ']', $sql, mysql_error()); 
			$n = mysql_fetch_array($new_result); 
			if (date('Y-m-d H', strtotime($n['joindate'])) == date('Y-m-d H', strtotime($line['date'])) AND $n['password'] != 'NONMEMBER') { 
				$link = "profile.php?member=" . $line['userid']; 
				$title = getUserDisplayName($line['userid'])." ".$LANG['joined_site']; 
			} else { 
				if ($line['userid'] != $line['id2']) { 
					$title = getUserDisplayName($line['id2']) . $LANG['joined_site']; 
					$link = "profile.php?member=" . $line['id2']; 
				} else { 
					$title = getUserDisplayName($line['id2']) . $LANG['upd_address2']; 
					$link = "profile.php?member=" . $line['id2']; 
				}
			} 
		} elseif ($line['type'] == 'NEWS') { 
			$title = getUserDisplayName($line['userid']) . " " . $LANG['new_news1'] . " '" . $line['title'] . "' " . $LANG['new_news2']; 
			$link = "familynews.php?getnews=" . $line['userid'] . "&amp;newsid=" . $line['id']; 
		} elseif ($line['type'] == 'PRAYERS') { 
			$title = getUserDisplayName($line['userid']) . " " . $LANG['added_concern'] . " '" . $line['title'] . "'"; 
			$link = "prayers.php"; 
		} elseif ($line['type'] == 'RECIPES') { 
			$title = getUserDisplayName($line['userid']) . " " . $LANG['added_recipe1'] . " '" . $line['title'] . "' " . $LANG['added_recipe2']; 
			switch ($line['id2']) { 
				case $LANG['appetizer']: $link = "recipes.php?category=1&amp;id=" . $line['id']; break; 
				case $LANG['breakfast']: $link = "recipes.php?category=2&amp;id=" . $line['id']; break; 
				case $LANG['dessert']: $link = "recipes.php?category=3&amp;id=" . $line['id']; break; 
				case $LANG['entree_meat']: $link = "recipes.php?category=4&amp;id=" . $line['id']; break; 
				case $LANG['entree_seafood']: $link = "recipes.php?category=5&amp;id=" . $line['id']; break; 
				case $LANG['entree_veg']: $link = "recipes.php?category=6&amp;id=" . $line['id']; break; 
				case $LANG['salad']: $link = "recipes.php?category=7&amp;id=" . $line['id']; break; 
				case $LANG['side_dish']: $link = "recipes.php?category=8&amp;id=" . $line['id']; break; 
				case $LANG['soup']: $link = "recipes.php?category=9&amp;id=" . $line['id']; break; 
				default: $link = "recipes.php"; break; 
			} 
		} elseif ($line['type'] == 'GALLERY') { 
			$title = getUserDisplayName($line['userid']) . " " . $LANG['added_photos1'] . " " . $line['id2'] . " ". $LANG['added_photos2'] . " '" . $line['title'] . "' " . $LANG['added_photos3']; 
			$link = "gallery/index.php?uid=" . $line['userid'] . "&amp;cid=" . $line['id']; 
		} elseif ($line['type'] == 'NEWSCOM') { 
			$title = getUserDisplayName($line['userid']) . " " . $LANG['com_news']; 
			$link = "familynews.php?getnews=" . $line['userid'] . "&amp;newsid=".$line['id']; 
		} elseif ($line['type'] == 'GALCOM') { 
			$title = getUserDisplayName($line['userid']) . " " . $LANG['com_gallery']; 
			$link = "gallery/index.php?commentpid=" . $line['id3']; 
		} 
		
		$output .= "<item><title>$title</title> 
		<pubDate>" . gmdate('D, d M Y H:i:s', strtotime($line['date'])) . " GMT</pubDate> 
		<link>$urlRoot/$link</link> 
		<guid isPermaLink=\"false\">$urlRoot $title " . gmdate('D, d M Y H:i:s', strtotime($line['date'])) . "</guid> 
		</item> 
		"; 
	} 
	
	$output .= "</channel></rss>"; 
	echo $output;
}

function displayFeedPhotoGallery () {
	global $LANG;
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
	<title>" . getSiteName() . " - " . $LANG['link_gallery'] . "</title> 
	<link>" . $url . "</link> 
	<description>" . getSiteName() . " - " . $LANG['link_gallery'] . " " . $LANG['rss_feed'] . "</description> 
	<language>" . $LANG['lang'] . "</language> 
	<managingEditor>" . getContactEmail() . "</managingEditor> 
	"; 
	
	foreach ($return as $line) {
		$title = htmlentities($line['caption']);
		if ($title == "") {
			$title = htmlentities($line['name']);
		}
		$output .= "<item><title>".$title."</title> 
		<pubDate>" . gmdate('D, d M Y H:i:s', strtotime($line['date'])) . " GMT</pubDate> 
		<link>".htmlentities( $urlroot."/gallery/photos/member".$line['user']."/".$line['filename'])."</link>              
		<description><![CDATA[<img src=\"$urlroot/gallery/photos/member".$line['user']."/".$line['filename']."\" border=\"0\" />]]></description> 
		<enclosure url=\"".$urlroot."/gallery/photos/member".$line['user']."/".$line['filename']."\" type=\"". returnMIMEType("./gallery/photos/member".$line['user']."/".$line['filename']) ."\" length=\"". filesize("./gallery/photos/member".$line['user']."/".$line['filename'])."\" /> 
		<guid isPermaLink=\"true\">".$urlroot."/gallery/photos/member".$line['user']."/".$line['filename']."</guid> 
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
} 
?>