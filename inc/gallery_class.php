<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

class PhotoGallery {

	var $db;
	var $db2;
	var $tz_offset;
	var $cur_user_id;
	var $categories_per_row;

	function PhotoGallery ($current_user_id, $type, $host, $database, $user, $pass) {
		$this->categories_per_row = 4;
		$this->cur_user_id = $current_user_id;
		$this->db = new database($type, $host, $database, $user, $pass);
		$this->db2 = new database($type, $host, $database, $user, $pass);
		$sql = "SELECT timezone FROM fcms_users WHERE id = $current_user_id";
		$this->db->query($sql) or displaySQLError('Timezone Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error());
		$row = $this->db->get_row();
		$this->tz_offset = $row['timezone'];
	}

	function displayGalleryMenu ($uid = '0') {
		global $LANG;
		echo "<div class=\"gal_menu\">\n\t\t\t\t<div class=\"clearfix\"><a class=\"link_block home\" href=\"index.php\">".$LANG['gallery_home']."</a> <a class=\"link_block member\" href=\"?uid=0\">".$LANG['member_gal']."</a> ";
		echo "<a class=\"link_block rated\" href=\"?uid=$uid&amp;cid=toprated\">".$LANG['top_rated']."</a> <a class=\"link_block viewed\" href=\"?uid=$uid&amp;cid=mostviewed\">".$LANG['most_viewed']."</a></div>\n";
		if (checkAccess($this->cur_user_id) <= 3 || checkAccess($this->cur_user_id) == 8 || checkAccess($this->cur_user_id) == 5) {
			echo "\t\t\t\t<div class=\"clearfix\"><b>".$LANG['actions'].": </b><a class=\"link_block_sub\" href=\"?action=upload\">".$LANG['upload_photos']."</a> <a class=\"link_block_sub\" href=\"?action=category\">".$LANG['create_edit_cat']."</a></div>\n";
		}
		echo "\t\t\t</div>\n";
	}

	function displaySideMenu () {
		global $LANG;
		echo "\t<div class=\"gal_sidemenu\"><b>".$LANG['link_gallery']."</b><br/><b>Viewing Options</b><br/><br/>\n\t\t<a href=\"?uid=".$this->cur_user_id."\">".$LANG['view_my_photos']."</a><br/>\n\t\t<a href=\"?uid=0&amp;cid=".$this->cur_user_id."\">".$LANG['view_photos_me']."</a><br/>\n\t\t";
		$sql = "SELECT `id` FROM `fcms_users` WHERE `activated` > 0 AND `id` != " . $this->cur_user_id;
		$this->db->query($sql) or displaySQLError('Members Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error());
		if ($this->db->count_rows() > 0) { 
			echo "<form action=\"index.php\" method=\"get\">".$LANG['view_photos_of']."<br/><input type=\"hidden\" name=\"uid\" value=\"0\"/><select name=\"cid\">";
			while ($row = $this->db->get_row()) {
				$displayNameArr[$row['id']] = getUserDisplayName($row['id']);
			}
			asort($displayNameArr);
			foreach ($displayNameArr as $key => $value) {
				echo "<option value=\"$key\">$value</option>";
			}
			echo "</select> <input type=\"submit\" value=\"View\"/></form>";
		}
		echo "</div>\n\t";
	}

	function displayLatestCategories() {
		global $LANG;
		$sql = "SELECT p.`id`, p.`date`, p.`filename`, c.`name`, p.`user`, p.`category` FROM `fcms_gallery_photos` AS p, `fcms_gallery_category` AS c WHERE p.`category` = c.`id` GROUP BY `category` ORDER BY `date` DESC LIMIT 4";
		$this->db->query($sql) or displaySQLError('Latest Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error());
		while ($row = $this->db->get_row()) {
			$monthName = date('M', strtotime($row['date']));
			$date = date('. j, Y', strtotime($row['date']));
			$cat_array[] = "<div class=\"cat_name\">".$row['name']."</div><a href=\"?uid=" . $row['user'] . "&amp;cid=" . $row['category'] . "&amp;pid=" . $row['id'] . "\"><img class=\"photo\" src=\"photos/member" . $row['user'] . "/tb_" . $row['filename'] . "\" alt=\"\"/></a><div class=\"cat_info\">".$LANG[$monthName]."$date</div>";
		}
		if (!empty($cat_array)) {
			echo "\t\t\t<h3>".$LANG['latest_cat']."</h3>\n\t\t\t<div class=\"gal_row clearfix\">\n\t\t\t\t";
			foreach ($cat_array as $cat_link) {
				echo "<div class=\"cat\">$cat_link</div>";
			}
			echo "\n\t\t\t</div>\n";
		} else {
			echo "<div class=\"info-alert\"><p><h2>".$LANG['info_gal_empty1']."</h2></p><p><i>".$LANG['info_gal_empty2']."</i></p><p><b>".$LANG['info_gal_empty3']."</b><br/>".$LANG['info_gal_empty4']." <a href=\"?action=category\">".$LANG['create_edit_cat']."</a> ".$LANG['info_gal_empty5']." <a href=\"?action=upload\">".$LANG['upload_photos']."</a>.</p></div>";
		}
	}

	function showPhoto ($uid, $cid, $pid) {
		global $LANG;
		
		// Select all photos for the category/group you are trying to view
		if (strpos($cid, 'comments') !== false) {
			$special_breadcrumbs = "<a href=\"?uid=0&amp;cid=comments\">" . $LANG['latest_comments'] . "</a>";
			$urlcid = $cid;
			$cid = substr($cid, 8);
			$sql = "SELECT DISTINCT `filename` FROM (SELECT p.`filename` FROM `fcms_gallery_comments` AS c, `fcms_gallery_photos` AS p WHERE c.`photo` = p.`id` ORDER BY c.`date` DESC) as z";
		} elseif (strpos($cid, 'toprated') !== false) {
			$special_breadcrumbs = "<a href=\"?uid=0&amp;cid=toprated\">" . $LANG['top_rated'] . "</a>";
			$urlcid = $cid;
			$cid = substr($cid, 8);
			$sql = "SELECT `filename` FROM `fcms_gallery_photos` WHERE `votes` > 0 ORDER BY `rating`/`votes` DESC";
		} elseif (strpos($cid, 'mostviewed') !== false) {
			$special_breadcrumbs = "<a href=\"?uid=$uid&amp;cid=$cid\">" . $LANG['most_viewed'] . "</a>";
			$urlcid = $cid;
			$cid = substr($cid, 10);
			$sql = "SELECT `filename` FROM `fcms_gallery_photos` WHERE `views` > 0";
			if ($uid > 0) {
				$sql .= " AND `user` = $uid";
			}
			$sql .= " ORDER BY `views` DESC";
			
		// Tagged Photos -- here $cid = 'tagged' plus the tagged user's id
		} elseif (strpos($cid, 'tagged') !== false) {
			$urlcid = $cid;
			$cid = substr($cid, 6);
			$special_breadcrumbs = "<a href=\"?uid=0&amp;cid=$cid\">" . $LANG['photos_of'] . " " . getUserDisplayName($cid) . "</a>";
			$sql = "SELECT `filename` FROM `fcms_gallery_photos` AS p, `fcms_gallery_photos_tags` AS t WHERE t.`user` = $cid AND t.`photo` = p.`id` ORDER BY `date`";
			
		// Category of Photos
		} elseif (preg_match('/^\d+$/', $cid)) {
			$urlcid = $cid;
			$sql = "SELECT `filename` FROM `fcms_gallery_photos` WHERE `category` = $cid ORDER BY `date`";
		}
		$this->db2->query($sql) or displaySQLError('Photos Error', 'gallery_class.php', $sql, mysql_error());
		while ($row = $this->db2->get_row()) {
			$photo_arr[] = $row['filename'];
		}
		
		// Select Current Photo to view
		$sql = "SELECT p.`user` AS uid, `filename`, `caption`, `category` AS cid, `name` AS category_name, `views`, `votes`, `rating` FROM `fcms_gallery_photos` AS p, `fcms_gallery_category` AS c WHERE p.`id` = $pid AND p.`category` = c.`id`";
		$this->db->query($sql) or displaySQLError('Photo Error', 'gallery_class.php', $sql, mysql_error());
		if ($this->db->count_rows() > 0) {
			$r = $this->db->get_row();
			$displayname = getUserDisplayName($r['uid']);
			
			// Update View count
			$sql = "UPDATE `fcms_gallery_photos` SET `views` = `views`+1 WHERE `id` = $pid";
			$this->db->query($sql) or displaySQLError('Update View Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error());
			
			// What type of breadcrumbs
			if (isset($special_breadcrumbs)) {
				echo "<p class=\"breadcrumbs\">$special_breadcrumbs</p>";
				echo "<small>" . $LANG['from_the_cat'] . ": <a href=\"?uid=" . $r['uid'] . "&amp;cid=" . $r['cid'] . "\">".$r['category_name']."</a> " . $LANG['by'] . " <a class=\"u\" href=\"../profile.php?member=" . $r['uid'] . "\">$displayname</a></small>\n";
			} else {
				echo "<p class=\"breadcrumbs\"><a href=\"?uid=0\">".$LANG['member_gal']."</a> &gt; <a href=\"?uid=$uid\">$displayname</a> &gt; <a href=\"?uid=$uid&amp;cid=$cid\">".$r['category_name']."</a></p>\n";
			}
			
			// Display Next / Previous links
			$total_photos = count($photo_arr);
			$cur = array_search($r['filename'], $photo_arr);
			if (isset($photo_arr[$cur-1])) {
				$prev_pid = substr($photo_arr[$cur-1], 0, strpos($photo_arr[$cur-1], '.'));
			}
			if (isset($photo_arr[$cur+1])) {
				$next_pid = substr($photo_arr[$cur+1], 0, strpos($photo_arr[$cur+1], '.'));
			}
			if (!isset($prev_pid) && !isset($next_pid)) {
				echo "<p class=\"center\">".$LANG['photo']." "; echo $cur+1; echo " ".$LANG['of']." $total_photos</p>";
			} elseif (!isset($prev_pid)) {
				echo "<p class=\"center\">".$LANG['photo']." "; echo $cur+1; echo " ".$LANG['of']." $total_photos <a href=\"?uid=$uid&amp;cid=$urlcid&amp;pid=$next_pid\">>></a></p>";
			} elseif (!isset($next_pid)) {
				echo "<p class=\"center\"><a href=\"?uid=$uid&amp;cid=$urlcid&amp;pid=$prev_pid\"><<</a> ".$LANG['photo']." "; echo $cur+1; echo " ".$LANG['of']." $total_photos</p>";
			} else {
				echo "<p class=\"center\"><a href=\"?uid=$uid&amp;cid=$urlcid&amp;pid=$prev_pid\"><<</a> ".$LANG['photo']." "; echo $cur+1; echo " ".$LANG['of']." $total_photos <a href=\"?uid=$uid&amp;cid=$urlcid&amp;pid=$next_pid\">>></a></p>";
			}
			
			// Check access level to see if you can Edit/Del photo
			if($this->cur_user_id == $r['uid'] || checkAccess($this->cur_user_id) < 2) {
				echo "<div class=\"edit_del_photo\"><form class=\"frm_line\" action=\"index.php\" method=\"post\"><div><input type=\"hidden\" name=\"photo\" id=\"photo\" value=\"$pid\"/>".$LANG['edit']." <input type=\"submit\" name=\"editphoto\" id=\"editphoto\" value=\" \" class=\"gal_editbtn\" /></div></form>&nbsp;&nbsp;<form class=\"frm_line\" action=\"index.php\" method=\"post\"><div><input type=\"hidden\" name=\"photo\" id=\"photo\" value=\"$pid\"/>".$LANG['delete']." <input type=\"submit\" name=\"deletephoto\" id=\"addcom\" value=\" \" class=\"gal_delbtn\" onclick=\"javascript:return confirm('".$LANG['js_del_photo']."'); \"/></div></form></div>";
			}
			
			// Display photo and caption
			echo "<p class=\"center\"><a href=\"photos/member" . $r['uid'];
			// Link to the full sized photo if using full sized
			$sql = "SELECT `full_size_photos` FROM `fcms_config`";
			$this->db->query($sql) or displaySQLError('Full Size Error', 'gallery_class.php', $sql, mysql_error());
			$row = $this->db->get_row();
			if ($row['full_size_photos'] == 1) {
				// If you are using full sized but a photo was uploaded prior to that change, no full sized photo will be available, so don't link to it
				if (file_exists("photos/member" . $r['uid'] . "/full_" . $r['filename'])) {
					echo "/full_";
				} else {
					echo "/";
				}
			} else {
				echo "/";
			}
			echo $r['filename'] . "\"><img class=\"photo\" src=\"photos/member" . $r['uid'] . "/" . $r['filename'] . "\" alt=\"".htmlentities($r['caption'], ENT_COMPAT, 'UTF-8')."\" /></a></p>";
			echo "<div class=\"comment_block\"><p class=\"center\">".htmlentities($r['caption'], ENT_COMPAT, 'UTF-8')."</p></div>";
			
			// Display rating & views
			if ($r['votes'] <= 0) { $rating = 0; $width = 0; } else { $rating = ($r['rating'] / $r['votes']) * 100; $width = $rating / 5; }
			echo "<ul class=\"star-rating small-star\"><li class=\"current-rating\" style=\"width:$width%\">Currently " . $r['rating'] . "/5 Stars.</li><li><a href=\"?uid=$uid&amp;cid=$cid&amp;pid=$pid&amp;vote=1\" title=\"".$LANG['title_stars1']."\" class=\"one-star\">1</a></li><li><a href=\"?uid=$uid&amp;cid=$cid&amp;pid=$pid&amp;vote=2\" title=\"".$LANG['title_stars2']."\" class=\"two-stars\">2</a></li><li><a href=\"?uid=$uid&amp;cid=$cid&amp;pid=$pid&amp;vote=3\" title=\"".$LANG['title_stars3']."\" class=\"three-stars\">3</a></li><li><a href=\"?uid=$uid&amp;cid=$cid&amp;pid=$pid&amp;vote=4\" title=\"".$LANG['title_stars4']."\" class=\"four-stars\">4</a></li><li><a href=\"?uid=$uid&amp;cid=$cid&amp;pid=$pid&amp;vote=5\" title=\"".$LANG['title_stars5']."\" class=\"five-stars\">5</a></li></ul>";
			echo "<p class=\"center\"><small>".$LANG['views'].": " . $r['views'] . "</small></p>";
			
			// Display Comments
			if (checkAccess($_SESSION['login_id']) <= 8 && checkAccess($_SESSION['login_id']) != 7 && checkAccess($_SESSION['login_id']) != 4) {
				echo "<p class=\"center\">&nbsp;</p>\n\t\t\t<h3>".$LANG['comments']."</h3><p class=\"center\"><form action=\"?uid=$uid&amp;cid=$urlcid&amp;pid=$pid\" method=\"post\">".$LANG['add_comment']."<br/><input type=\"text\" name=\"comment\" id=\"comment\" size=\"50\" title=\"".$LANG['add_comment']."\"/> <input type=\"submit\" name=\"addcom\" id=\"addcom\" value=\" \" class=\"gal_addcombtn\" /></form></p><p class=\"center\">&nbsp;</p>";
				$sql = "SELECT c.`id`, `comment`, `date`, `fname`, `lname`, `displayname`, `username`, `user` FROM `fcms_gallery_comments` AS c, `fcms_users` AS u WHERE `photo` = '$pid' AND c.`user` = u.`id` ORDER BY `date`";
				$this->db->query($sql) or displaySQLError('Comments Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error());
				if ($this->db->count_rows() > 0) { 
					while ($row = $this->db->get_row()) {
						$displayname = getUserDisplayName($row['user']);
						$date = fixDST(gmdate('Y-m-d h:i:s', strtotime($row['date'] . $this->tz_offset)), $this->cur_user_id, 'M. d, Y (h:i a)');
						if ($this->cur_user_id == $row['user'] || checkAccess($this->cur_user_id) < 2) {
							echo "<div class=\"comment_block\"><form action=\"?page=photo&amp;uid=$uid&amp;cid=$urlcid&amp;pid=$pid\" method=\"post\"><input type=\"submit\" name=\"delcom\" id=\"delcom\" value=\" \" class=\"gal_delcombtn\" title=\"".$LANG['title_del_comment']."\" onclick=\"javascript:return confirm('".$LANG['js_del_comment']."'); \"/><span>$date</span><b>$displayname</b><br/>".htmlentities($row['comment'], ENT_COMPAT, 'UTF-8')."<input type=\"hidden\" name=\"id\" value=\"".$row['id']."\"></form></div>";
						} else {
							echo "<div class=\"comment_block\"><span>$date</span><b>$displayname</b><br/>".htmlentities($row['comment'], ENT_COMPAT, 'UTF-8')."</div>";
						}
					}
				} else {
					echo "<p class=\"center\">".$LANG['no_comments']."</p>";
				}
			}
			
		// SQL returns no results - notify user that photo couldn't be found
		} else {
			echo "<p class=\"error-alert\">".$LANG['err_photo_not_found']."</p>";
		}
	}

	function showCategories ($from, $uid, $cid = 'none') {
		global $LANG;
		
		// No user id specified
		if ($uid == 0) {
			
			// Member Gallery View
			if ($cid == 'none') {
				$sql = "SELECT 'MEMBER' AS type, u.`id` AS uid, f.`filename`, COUNT(p.`id`) as c FROM `fcms_gallery_category` AS cat LEFT JOIN `fcms_gallery_photos` AS p ON p.`category` = cat.`id`, `fcms_users` AS u, (SELECT * FROM `fcms_gallery_photos` ORDER BY `date` DESC) AS f WHERE f.`id` = p.`id` AND u.`id` = p.`user` GROUP BY p.`user`";
				
			// All Latest Comments View
			} elseif ($cid == 'comments') {
				$sql = "SELECT 'COMMENTS' AS type, p.`user` AS uid, p.`category` AS cid, c.`date` AS heading, p.`id` AS pid, p.`filename`, c.`comment`, c.`user` FROM `fcms_gallery_comments` AS c, `fcms_gallery_photos` AS p, `fcms_gallery_category` AS cat, `fcms_users` AS u WHERE c.`photo` = p.`id` AND p.`category` = cat.`id` AND c.`user` = u.`id` ORDER BY c.`date` DESC";
				
			// Overall Top Rated View
			} elseif ($cid == 'toprated') {
				$sql = "SELECT 'RATED' AS type, `user` AS uid, `filename`, `category`, `id` AS pid, `rating`/`votes` AS 'r' FROM `fcms_gallery_photos` WHERE `votes` > 0 ORDER BY r DESC";
				
			// Overall Most Viewed View
			} elseif ($cid == 'mostviewed') {
				$sql = "SELECT 'VIEWED' AS type, `user` AS uid, `filename`, `id` AS pid, `views` FROM `fcms_gallery_photos` WHERE `views` > 0 ORDER BY VIEWS DESC";
				
			// Tagged Photos View (only number 0-9)
			} elseif (preg_match('/^\d+$/', $cid)) {
				$sql = "SELECT 'TAGGED' AS type, t.`user`, p.`id` AS pid, p.`filename`, p.`user` AS uid FROM `fcms_gallery_photos` AS p, `fcms_gallery_photos_tags` AS t WHERE t.`user` = $cid AND t.`photo` = p.`id`";
			}
			
		// Valid user id specified
		} elseif (preg_match('/^\d+$/', $uid)) {
			
			// Member's Top Rated View
			if ($cid == 'toprated') {
				$sql = "SELECT 'RATED' AS type, `user` AS uid, `filename`, `category`, `id` AS pid, `rating`/`votes` AS 'r' FROM `fcms_gallery_photos` WHERE `votes` > 0 AND `user` = $uid ";
				
			// Member's Most Viewed View
			} elseif ($cid == 'mostviewed') {
				$sql = "SELECT 'VIEWED' AS type, `user` AS uid, `filename`, `id` AS pid, `views` FROM `fcms_gallery_photos` WHERE `views` > 0 AND `user` = $uid ";
				
			// Photo Listing for Member's Sub Category (only numbers 0-9)
			} elseif (preg_match('/^\d+$/', $cid)) {
				$sql = "SELECT 'PHOTOS' AS type, u.`id` AS uid, `category` AS cid, p.`id` AS pid, `caption`, c.`name` AS category, `filename` FROM `fcms_gallery_category` AS c, `fcms_gallery_photos` AS p, `fcms_users` AS u WHERE p.`user` = u.`id` AND `category` = c.`id` AND `category` = $cid";
				
			// Member's Sub Categories View
			// invalid $cid's will default to member's sub cat listing
			} else {
				$sql = "SELECT 'CATEGORIES' AS type, u.`id` AS uid, cat.`name` AS category, cat.`id` AS cid, f.`filename`, COUNT(p.`id`) AS c FROM `fcms_gallery_category` AS cat LEFT JOIN `fcms_gallery_photos` AS p ON p.`category` = cat.`id`, `fcms_users` AS u, (SELECT * FROM `fcms_gallery_photos` ORDER BY `date` DESC) AS f WHERE f.`id` = p.`id` AND u.`id` = p.`user` AND p.`user`=$uid GROUP BY cat.`id` DESC";
			}
		// Catch all invalid $uid's
		} else {
			echo "<div class=\"info-alert\"><h2>" . $LANG['no_category1'] . "</h2><p>" . $LANG['no_category2'] . "</p></div>";
		}
		// Starting with what page?  -- used with Latest Comments, Top Rated and Most Viewed
		if ($from >= 0) {
			$sql .= " LIMIT $from, 16";
		} else {
			$sql .= " LIMIT 8";
		}
		$this->db->query($sql) or displaySQLError('Categories Error', 'gallery_class.php', $sql, mysql_error());
		if ($this->db->count_rows() > 0) {
			$first = true;
			while ($row = $this->db->get_row()) {
				$cat_name = "";  $url = "";  $alt = "";  $cat_info = "";
				if ($row['type'] == 'MEMBER') {
					if ($first) {
						echo "<h3>" . $LANG['member_gal'] . "</h3>";
					}
					$displayname = getUserDisplayName($row['uid']);
					$cat_name = "<div class=\"cat_name\">$displayname</div>";
					$url = "?uid=" . $row['uid'];
					$alt = " alt=\"" . $LANG['alt_view_cat_for'] . " " . htmlentities($displayname, ENT_COMPAT, 'UTF-8') . "\"";
					$cat_info = "<div class=\"cat_info\">" . $LANG['photos'] . " (" . $row['c'] . ")</div>";
				} elseif ($row['type'] == 'COMMENTS') {
					if ($first) {
						if ($from >= 0) {
							echo "\t\t\t<h3>" . $LANG['latest_comments'] . "</h3>\n";
						} else {
							echo "\t\t\t<h3 style=\"float:left\">" . $LANG['latest_comments'] . "</h3><a style=\"float:left; margin-left:5px;\" href=\"?uid=0&amp;cid=comments\">(".$LANG['view_all'].")</a>\n";
						}
					}
					$monthName = fixDST(gmdate('F j, Y g:i a', strtotime($row['heading'] . $this->tz_offset)), $this->cur_user_id, 'M');
					$date = fixDST(gmdate('F j, Y g:i a', strtotime($row['heading'] . $this->tz_offset)), $this->cur_user_id, '. j, Y');
					$date = $LANG[$monthName] . $date;
					$cat_name = "<div class=\"cat_name\">$date</div>";
					$url = "?uid=0&amp;cid=comments&amp;pid=" . $row['pid'];
					$comment = $row['comment'];
					if(strlen($comment) > 25) { $comment = substr($comment, 0, 22) . "..."; }
					$cat_info = "<div class=\"cat_info\"><b>" . getUserDisplayName($row['user']) . ":</b> $comment</div>";
				} elseif ($row['type'] == 'RATED') {
					if ($first) {
						echo "\t\t\t<h3>" . $LANG['top_rated'];
						if ($uid > 0) {
							echo " (" . getUserDisplayName($uid) . ")";
						}
						echo "</h3>\n";
					}
					$width = ($row['r'] / 5) * 100;
					$url = "?uid=0&amp;cid=toprated" . $row['category'] . "&amp;pid=" . $row['pid'];
					$cat_info .= "<div class=\"cat_info\"><ul class=\"star-rating small-star\"><li class=\"current-rating\" style=\"width:$width%\">Currently " . $row['r'] . "/5 Stars.</li><li><a href=\"$url&amp;vote=1\" title=\"".$LANG['title_stars1']."\" class=\"one-star\">1</a></li><li><a href=\"$url&amp;vote=2\" title=\"".$LANG['title_stars2']."\" class=\"two-stars\">2</a></li><li><a href=\"$url&amp;vote=3\" title=\"".$LANG['title_stars3']."\" class=\"three-stars\">3</a></li><li><a href=\"$url&amp;vote=4\" title=\"".$LANG['title_stars4']."\" class=\"four-stars\">4</a></li><li><a href=\"$url&amp;vote=5\" title=\"".$LANG['title_stars5']."\" class=\"five-stars\">5</a></li></ul></div>";
				} elseif ($row['type'] == 'VIEWED') {
					if ($first) {
						echo "\t\t\t<h3>" . $LANG['most_viewed'];
						if ($uid > 0) {
							echo " (" . getUserDisplayName($uid) . ")";
						}
						echo "</h3>\n";
					}
					$url = "?uid=$uid&amp;cid=mostviewed&amp;pid=" . $row['pid'];
					$cat_info = "<div class=\"cat_info\"><b>".$LANG['views'].": </b>" . $row['views'] . "</div>";
				} elseif ($row['type'] == 'TAGGED') {
					if ($first) {
						echo "\t\t\t<h3>" . $LANG['photos_of'] . " " . getUserDisplayName($row['user']) . "</h3>\n";
					}
					$url = "?uid=0&amp;cid=tagged" . $row['user'] . "&amp;pid=" . $row['pid'];
				} elseif ($row['type'] == 'PHOTOS') {
					if ($first) {
						echo "\t\t\t<p class=\"breadcrumbs\"><a href=\"?uid=0\">".$LANG['member_gal']."</a> &gt; <a href=\"?uid=$uid\">" . getUserDisplayName($row['uid']) . "</a> &gt; " . $row['category'] . "</p>\n";
					}
					$url = "?uid=" . $row['uid'] . "&amp;cid=" . $row['cid'] . "&amp;pid=" . $row['pid'];
					$alt = " alt=\"" . htmlentities($row['caption'], ENT_COMPAT, 'UTF-8') . "\"";
				} elseif ($row['type'] == 'CATEGORIES') {
					if ($first) {
						echo "\t\t\t<p class=\"breadcrumbs\"><a href=\"?uid=0\">".$LANG['member_gal']."</a> &gt; " . getUserDisplayName($row['uid']) . "</p>\n";
					}
					$cat_name = "<div class=\"cat_name\">" . $row['category'] . "</div>";
					$url = "?uid=" . $row['uid'] . "&amp;cid=" . $row['cid'];
					$alt = " alt=\"" . $LANG['alt_view_photos_in'] . " " . htmlentities($row['category'], ENT_COMPAT, 'UTF-8') . "\"";
					$cat_info = "<div class=\"cat_info\">" . $LANG['photos'] . " (" . $row['c'] . ")</div>";
				}
				$category_rows[] = "$cat_name<a href=\"index.php$url\"><img class=\"photo\" src=\"photos/member" . $row['uid'] . "/tb_" . $row['filename'] . "\" $alt/></a>$cat_info";
				$first = false;
			}
			echo "\t\t\t<div class=\"gal_row clearfix\">";
			$i = 0;
			foreach ($category_rows as $row) {
				if ($i == $this->categories_per_row) {	
					$i = 1;
					echo "</div>\n\t\t\t<div class=\"gal_row clearfix\">";
				} else {
					$i++;
				}
				echo "<div class=\"cat\">$row</div>";
			}
			echo "</div>\n";
			
			// Display pages if needed
			if ($from >= 0) {
				$page = ($from / 16) + 1;
				
				// Remove the LIMIT from the $sql statement 
				// used above, so we can get the total count
				$sql = substr($sql, 0, strpos($sql, 'LIMIT'));
				
				// Setup the pages URL link
				if (preg_match('/^\d+$/', $cid)) {
					// Remove the pid (link to a specific photo) from the $url to be used in pages url
					$pos = strpos($url, '&amp;pid=');
					if ($pos !== false) {
						$url = substr($url, 0, $pos);
					}
				} elseif ($uid == 0) {
					// Reset the uid to 0 on the url
					$url = "?uid=0";
				} else {
					// Remove the cid (link to a specific category) from the $url to be used in pages url
					$pos = strpos($url, '&amp;cid=');
					if ($pos !== false) {
						$url = substr($url, 0, $pos);
					}
				}
				
				$this->db->query($sql) or displaySQLError('Categories Page Count Error', 'gallery_class.php', $sql, mysql_error());
				$count = $this->db->count_rows();
				$total_pages = ceil($count / 16); 
				displayPages("index.php$url", $page, $total_pages);
			}
			
		// If the sql statement returns no results and we're not trying to show the latest 8 comments on the index
		} elseif ($uid !== 0 && $cid !== 'comments') {
			echo "\t\t\t<div class=\"info-alert\"><h2>".$LANG['info_cat_empty1']."</h2><p>".$LANG['info_cat_empty2']."</p></div>\n";
		}
	}

	function displayUploadForm ($num, $last_cat) {
		global $LANG;
		echo "<h3>".$LANG['upload_photos']."</h3><p style=\"width: 90%; text-align: right;\"><a class=\"help\" href=\"../help.php#gallery-howworks\">".$LANG['link_help']."</a></p>";
		echo "<script src=\"../inc/prototype.js\" type=\"text/javascript\"></script><form enctype=\"multipart/form-data\" action=\"?action=upload\" method=\"post\"><p>".$LANG['select_cat']." <select name=\"category\">";
		$sql = "SELECT `id`, `name` FROM `fcms_gallery_category` WHERE `user` = " . $this->cur_user_id;
		$this->db->query($sql) or displaySQLError('Category Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error());
		while($row = $this->db->get_row()) {
			echo "<option ";
			if ($last_cat == $row['id']) {
				echo "selected=\"selected\" ";
			}
			echo "value=\"" . $row['id'] . "\">".$row['name']."</option>";
		}
		echo "</select></p><br/>";
		$i = 1;
		while($i <= $num) {
			if ($num > 1) { echo "<p>".$LANG['photo']." ($i):"; } else { echo "<p>".$LANG['photo'].":"; }
			echo " <input name=\"photo_filename[]\" type=\"file\" size=\"50\"/>";
			if ($num <= 1) { echo "<br/><a href=\"#\" onclick=\"$('upload-options$i').toggle(); return false\">".$LANG['upload_options']."</a>"; }
			echo "</p>";
			if ($num <= 1) {
				echo "<div id=\"upload-options$i\" style=\"display:none;\"><p>".$LANG['rotate_left'].": <input type=\"radio\" name=\"rotate[]\" value=\"left\"/>&nbsp;&nbsp; ".$LANG['rotate_right'].": <input type=\"radio\" name=\"rotate[]\" value=\"right\"/></p>";
				echo "<p>".$LANG['who_in_photo'].": <select style=\"valign:top\" name=\"tagged[]\" multiple=\"multiple\" size=\"5\" style=\"vertical-align:top; width:150px;\">";
				$sql = "SELECT `id` FROM `fcms_users` WHERE `activated` > 0";
				$this->db2->query($sql) or displaySQLError('Members Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error());
				while ($r = $this->db2->get_row()) {
					$displayNameArr[$r['id']] = getUserDisplayName($r['id']);
				}
				asort($displayNameArr);
				foreach ($displayNameArr as $key => $value) {
					echo "<option value=\"$key\">$value</option>";
				}
				echo "</select></p></div>";
			}
			echo "<p>".$LANG['caption'].": <input type=\"text\" name=\"photo_caption[]\" size=\"50\"/></p>";
			if ($num > 1) { echo "<p>&nbsp;</p>"; }
			$i++;
		}
		echo "<br/><input type=\"submit\" id=\"addphoto\" name=\"addphoto\" value=\"".$LANG['submit']."\" /></form><p>&nbsp;</p>";
	}

	function displayEditPhotoForm ($photo) {
		global $LANG;
		echo "<h4>".$LANG['edit_photo']."</h4>\n\t\t\t";
		$sql = "SELECT p.`user`, `filename`, `caption`, `name` FROM `fcms_gallery_photos` AS p, `fcms_gallery_category` AS c WHERE p.`id` = $photo AND p.`category` = c.`id`";
		$this->db->query($sql) or displaySQLError('Photo Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error());
		if ($this->db->count_rows() > 0) {
			$row = $this->db->get_row();
			$photo_user = $row['user'];
			$filename = $row['filename'];
			$caption = $row['caption'];
			$category = $row['name'];
			echo "<script src=\"inc/prototype.js\" type=\"text/javascript\"></script>\n\t\t\t<form enctype=\"multipart/form-data\" action=\"index.php\" method=\"post\">\n\t\t\t".$LANG['change_cat']." \n\t\t\t<select name=\"category\">\n\t\t\t";
			$sql = "SELECT `id`, `name` FROM `fcms_gallery_category` WHERE `user` = $photo_user";
			$this->db->query($sql) or displaySQLError('Category Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error());
			while($row = $this->db->get_row()) {
				echo "\t<option value=\"$row[0]\""; if($category == $row[1]) { echo " selected=\"selected\""; } echo ">$row[1]</option>\n\t\t\t";
			}
			echo "</select>\n\t\t\t<p><img src='photos/member$photo_user/$filename'/></p>\n\t\t\t";
			echo "<p>".$LANG['caption'].": <input type=\"text\" name=\"photo_caption\" size=\"50\" value=\"$caption\"/></p>\n\t\t\t";
			echo "<p>\n\t\t\t\t".$LANG['who_in_photo'].": \n\t\t\t\t<select name=\"tagged[]\" multiple=\"multiple\" size=\"5\" style=\"vertical-align:top; width:150px;\">\n\t\t\t\t";
			$sql = "SELECT * FROM `fcms_gallery_photos_tags` WHERE `photo` = $photo";
			$this->db2->query($sql) or displaySQLError('Find Tagged Users For Edit Error', 'gallery_class.php', $sql, mysql_error());
			while ($r = $this->db2->get_row()) {
				$users_in_photo[$r['id']] = $r['user'];
			}
			$sql = "SELECT `id` FROM `fcms_users` WHERE `activated` > 0";
			$this->db2->query($sql) or displaySQLError('Members Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error());
			while ($r = $this->db2->get_row()) {
				$displayNameArr[$r['id']] = getUserDisplayName($r['id']);
			}
			asort($displayNameArr);
			foreach ($displayNameArr as $key => $value) {
				echo "\t<option value=\"$key\"";
				if (isset($users_in_photo)) {
					$found = array_search($key, $users_in_photo);
					if ($found !== false) {
						echo " selected=\"selected\"";
					}
				}
				echo ">$value</option>\n\t\t\t\t";
			}
			echo "</select>\n\t\t\t</p>\n\t\t\t";
			
			// Send over a string of the previous members who were tagged, only if someone was actually previously tagged
			if (isset($users_in_photo)) {
				echo "<input type=\"hidden\" name=\"prev_tagged_users\" value=\"";
				$first = true;
				foreach ($users_in_photo as $uid) {
					if ($first) {
						echo $uid;
					} else {
						echo "," . $uid;
					}
					$first = false;
				}
				echo "\"/>\n\t\t\t";
			}
			echo "<input type=\"hidden\" name=\"photo_id\" id=\"photo_id\" value=\"$photo\"/>\n\t\t\t<input type=\"submit\" name=\"add_editphoto\" value=\"".$LANG['submit_changes']."\" />\n\t\t\t</form>\n";
		} else {
			echo "<p class=\"error-alert\">".$LANG['err_edit_photo']."</p>\n";
		}
	}

	function uploadPhoto ($category, $photos_uploaded, $photos_caption, $rotateoptions, $stripcap) {
		global $LANG;
		$known_photo_types = array('image/pjpeg' => 'jpg', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/x-png' => 'png');
		$gd_function_suffix = array('image/pjpeg' => 'JPEG', 'image/jpeg' => 'JPEG', 'image/gif' => 'GIF', 'image/x-png' => 'PNG');
		$i = 0;
		
		// Are we using full sized photos?
		$sql = "SELECT `full_size_photos` FROM `fcms_config`";
		$this->db->query($sql) or displaySQLError('Full Size Error', 'gallery_class.php [' . __LINE__ . ']', $sql, mysql_error());
		$r = $this->db->get_row();
		
		while ($i < count($photos_uploaded['name'])) {
			if ($photos_uploaded['size'][$i] > 0) {
				if (!array_key_exists($photos_uploaded['type'][$i], $known_photo_types)) {
					echo "<p class=\"error-alert\">".$LANG['err_not_file1']." ".($i+1)." ".$LANG['err_not_file2']."</p><br />";
				} else {
					if ($stripcap == 'true') { $photos_caption[$i] = stripslashes($photos_caption[$i]); }
					$sql = "INSERT INTO `fcms_gallery_photos`(`date`, `caption`, `category`, `user`) VALUES(NOW(), '".addslashes($photos_caption[$i])."', '" . addslashes($category) . "', $this->cur_user_id)";
					$this->db->query($sql) or displaySQLError('Add Photo Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error());
					$new_id = mysql_insert_id();
					$filetype = $photos_uploaded['type'][$i];
					$extention = $known_photo_types[$filetype];
					$filename = $new_id . "." . $extention;
					$sql = "UPDATE `fcms_gallery_photos` SET `filename`='" . addslashes($filename) . "' WHERE id='" . addslashes($new_id) . "'";
					$this->db->query($sql) or displaySQLError('Update Photo Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error());
					if (!file_exists("photos/member" . $this->cur_user_id)) {
						mkdir("photos/member" . $this->cur_user_id);
					}
					copy($photos_uploaded['tmp_name'][$i], "photos/member" . $this->cur_user_id . "/" . $filename);
					
					// Get image sizes
					$size = GetImageSize("photos/member" . $this->cur_user_id . "/" . $filename);
					// Thumbnail should be no larger than 100px x 100px
					if ($size[0] > $size[1]) {
						if ($size[0] > 100) {
							$thumbnail_width = 100; $thumbnail_height = (int)(100 * $size[1] / $size[0]);
						} else {
							$thumbnail_width = $size[0]; $thumbnail_height = $size[1];
						}
					} else {
						if ($size[1] > 100) {
							$thumbnail_width = (int)(100 * $size[0] / $size[1]); $thumbnail_height = 100;
						} else {
							$thumbnail_width = $size[0]; $thumbnail_height = $size[1];
						}
					}
					// Middle sized photo should be no wider than 500px, height is ignored
					if ($size[0] > 500) {
						$main_width = 500; $main_height = (int)(500 * $size[1] / $size[0]);
					} else {
						$main_width = $size[0]; $main_height = $size[1];
					}
					
					// Resize the image
					$function_suffix = $gd_function_suffix[$filetype];
					$function_to_read = "ImageCreateFrom" . $function_suffix;
					$function_to_write = "Image" . $function_suffix;
					$source_handle = $function_to_read("photos/member" . $this->cur_user_id . "/" . $filename); 
					if($source_handle) {
						$thumb_destination_handle = ImageCreateTrueColor($thumbnail_width, $thumbnail_height);
						ImageCopyResampled($thumb_destination_handle, $source_handle, 0, 0, 0, 0, $thumbnail_width, $thumbnail_height, $size[0], $size[1]);
						$main_destination_handle = ImageCreateTrueColor($main_width, $main_height);
						ImageCopyResampled($main_destination_handle, $source_handle, 0, 0, 0, 0, $main_width, $main_height, $size[0], $size[1]);
						if ($r['full_size_photos'] == '1') {
							$full_destination_handle = ImageCreateTrueColor($size[0], $size[1]);
							ImageCopyResampled($full_destination_handle, $source_handle, 0, 0, 0, 0, $size[0], $size[1], $size[0], $size[1]);
						}
					}
					$function_to_write($thumb_destination_handle, "photos/member" . $this->cur_user_id . "/tb_" . $filename);
					$function_to_write($main_destination_handle, "photos/member" . $this->cur_user_id . "/" . $filename);
					if ($r['full_size_photos'] == '1') {
						$function_to_write($full_destination_handle, "photos/member" . $this->cur_user_id . "/full_" . $filename);
					}
					
					// File Rotation
					if ($rotateoptions[$i] == 'left' || $rotateoptions[$i] =='right') {
						if ($rotateoptions[$i] == 'left') {
							$rotate_thumb = imagerotate($thumb_destination_handle, 90, 0);
							$rotate_main = imagerotate($main_destination_handle, 90, 0);
							if ($r['full_size_photos'] == '1') {
								$rotate_full = imagerotate($full_destination_handle, 90, 0);
							}
						}
						if ($rotateoptions[$i] == 'right') {
							$rotate_thumb = imagerotate($thumb_destination_handle, 270, 0);
							$rotate_main = imagerotate($main_destination_handle, 270, 0);
							if ($r['full_size_photos'] == '1') {
								$rotate_full = imagerotate($full_destination_handle, 270, 0);
							}
						}
						// Save the new rotated image
						switch($function_suffix) {
							case 'JPEG':
								imagejpeg($rotate_thumb, "photos/member" . $this->cur_user_id . "/tb_" . $filename);
								imagejpeg($rotate_main, "photos/member" . $this->cur_user_id . "/" . $filename);
								if ($r['full_size_photos'] == '1') {
									imagejpeg($rotate_full, "photos/member" . $this->cur_user_id . "/full_" . $filename);
								}
								break;
							case 'GIF':
								imagegif($rotate_thumb, "photos/member" . $this->cur_user_id . "/tb_" . $filename);
								imagegif($rotate_main, "photos/member" . $this->cur_user_id . "/" . $filename);
								if ($r['full_size_photos'] == '1') {
									imagegif($rotate_full, "photos/member" . $this->cur_user_id . "/full_" . $filename);
								}
								break;
							case 'WBMP':
								imagewbmp($rotate_thumb, "photos/member" . $this->cur_user_id . "/tb_" . $filename);
								imagewbmp($rotate_main, "photos/member" . $this->cur_user_id . "/" . $filename);
								if ($r['full_size_photos'] == '1') {
									imagewbmp($rotate_full, "photos/member" . $this->cur_user_id . "/full_" . $filename);
								}
								break;
							case 'PNG':
								imagepng($rotate_thumb, "photos/member" . $this->cur_user_id . "/tb_" . $filename);
								imagepng($rotate_main, "photos/member" . $this->cur_user_id . "/" . $filename);
								if ($r['full_size_photos'] == '1') {
									imagepng($rotate_full, "photos/member" . $this->cur_user_id . "/full_" . $filename);
								}
								break;
							default:
								imagejpg($rotate_thumb, "photos/member" . $this->cur_user_id . "/tb_" . $filename);
								imagejpg($rotate_main, "photos/member" . $this->cur_user_id . "/" . $filename);
								if ($r['full_size_photos'] == '1') {
									imagejpg($rotate_full, "photos/member" . $this->cur_user_id . "/full_" . $filename);
								}
								break;
						}
					}
					ImageDestroy($thumb_destination_handle);
					ImageDestroy($main_destination_handle);
					if ($r['full_size_photos'] == '1') {
						ImageDestroy($full_destination_handle);
					}
					if (count($photos_uploaded['name']) > 1) {
						if ($i <= 0) { echo "<p class=\"ok-alert\"><b>".$LANG['ok_photos_success']."</b></p>"; }
						echo "<img src=\"photos/member" . $this->cur_user_id . "/tb_$filename\" alt=\"" . $photos_caption[$i] . "\"/>&nbsp;&nbsp;";
					} else {
						echo "<p class=\"ok-alert\"><b>".$LANG['ok_photos_success']."</b><br/><img src=\"photos/member" . $this->cur_user_id . "/tb_$filename\" alt=\"" . $photos_caption[$i] . "\"/></p>";
					}
				}
			}
			$i++;
		}
		if (isset($new_id)) {
			return $new_id;
		}
	}

	function displayAddCatForm () {
		global $LANG;
		echo "<h3>".$LANG['create_cat']."</h3>";
		echo "<form action=\"index.php?action=category\" method=\"post\">".$LANG['new_cat_name'].": <input type=\"text\" name=\"cat_name\" id=\"cat_name\" /><input type=\"submit\" name=\"newcat\" id=\"newcat\" value=\"".$LANG['add_cat']."\" /></form>";
		echo "\t<p>&nbsp;</p><h3>".$LANG['edit_cat']."</h3>\n\t\t<ul class=\"gallery_cat\">\n\t\t";
		$sql = "SELECT * FROM fcms_gallery_category WHERE user=" . $this->cur_user_id;
		$this->db->query($sql) or displaySQLError('Category Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error());
		if ($this->db->count_rows() > 0) {
			while ($row = $this->db->get_row()) {
				echo "<li><form class=\"frm_line\" action=\"index.php?action=category\" method=\"post\"><input type=\"text\" name=\"cat_name\" id=\"cat_name\" size=\"60\" value=\"".htmlentities($row['name'], ENT_COMPAT, 'UTF-8')."\"/><input type=\"hidden\" name=\"cid\" id=\"cid\" value=\"" . $row['id'] . "\"/> &nbsp;<input type=\"submit\" name=\"editcat\" class=\"editbtn\" value=\" \"/></form> &nbsp;";
				echo "<form class=\"frm_line\" action=\"index.php?action=category\" method=\"post\"><input type=\"hidden\" name=\"cid\" id=\"cid\" value=\"" . $row['id'] . "\"/><input type=\"submit\" name=\"delcat\" class=\"delbtn\" value=\" \" onclick=\"javascript:return confirm('".$LANG['js_del_cat']."'); \"/></form></li>\n\t\t";
			}
		} else {
			echo "<li><i>".$LANG['no_cats']."</i></li>";
		}
		echo "</ul><p>&nbsp;</p>";
	}

	function displayWhatsNewGallery () {
		global $LANG;
		$today = date('Y-m-d');
		$tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
		echo "\t\t\t\t<h3>".$LANG['link_gallery']."</h3>\n";
		$sql = "SELECT DISTINCT p.user, name AS category, p.category AS cid, DAYOFYEAR(`date`) AS d, COUNT(*) AS c FROM fcms_gallery_photos AS p, fcms_users AS u, fcms_gallery_category AS c WHERE p.user = u.id AND p.category = c.id AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) GROUP BY user, category, d ORDER BY `date` DESC LIMIT 0 , 5";
		$this->db->query($sql) or displaySQLError('Last 5 New Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error());
		if ($this->db->count_rows() > 0) {
			echo "\t\t\t\t<ul class=\"twolines\">\n";
			while($row = $this->db->get_row()) {
				$displayname = getUserDisplayName($row['user']);
				$category = $row['category'];
				$full_category = $row['category'];
				if (strlen($category) > 20) { $category = substr($category, 0, 17) . "..."; }
				$sql = "SELECT `date` FROM fcms_gallery_photos AS p, fcms_gallery_category AS c WHERE p.user = " . $row['user'] . " AND c.id = p.category AND c.name = '" . addslashes($full_category) . "' AND DAYOFYEAR(p.`date`) = " . $row['d'] . " ORDER BY `date` DESC LIMIT 1";
				$this->db2->query($sql) or displaySQLError('Date Error', 'inc/gallery_class.php [' . __LINE__ . ']', $sql, mysql_error());
				$found = $this->db2->get_row();
				$monthName = gmdate('M', strtotime($found['date'] . $this->tz_offset));
				$date = gmdate('. j, Y, g:i a', strtotime($found['date'] . $this->tz_offset));
				echo "\t\t\t\t\t<li";
				if (strtotime($found['date']) >= strtotime($today) && strtotime($found['date']) < strtotime($tomorrow)) { echo " class=\"new\""; }
				echo "><a href=\"gallery/index.php?uid=" . $row['user'] . "&amp;cid=" . $row['cid'] . "\" title=\"" . htmlentities($full_category, ENT_COMPAT, 'UTF-8') . "\">$category</a> - " . $row['c'] . " ".$LANG['new_photos']."<br/><span>".$LANG[$monthName]."$date - <a class=\"u\" href=\"profile.php?member=" . $row['user'] . "\">$displayname</a></span></li>\n";			
			}
			echo "\t\t\t\t</ul>\n";
		} else {
			echo "\t\t\t\t<ul><li><i>".$LANG['nothing_new_30']."</i></li></ul>\n";
		}
	}

} ?>