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
		$this->db->query("SELECT timezone FROM fcms_users WHERE id = $current_user_id") or die('<h1>Timezone Error (gallery.class.php 19)</h1>' . mysql_error());
		$row = $this->db->get_row();
		$this->tz_offset = $row['timezone'];
	}

	function displayLatestCategories() {
		global $LANG;
		$this->db->query("SELECT p.id, p.`date`, p.filename, c.name, p.user, p.category FROM fcms_gallery_photos AS p, fcms_gallery_category AS c WHERE p.category = c.id GROUP BY category ORDER BY `date` DESC LIMIT 4") or die('<h1>Latest Error (gallery.class.php 32)</h1>' . mysql_error());
		while ($row = $this->db->get_row()) {
			$monthName = date('M', strtotime($row['date']));
			$date = date('. j, Y', strtotime($row['date']));
			$cat_array[] = "<div class=\"cat_name\">".$row['name']."</div><a href=\"?uid=" . $row['user'] . "&amp;cid=" . $row['category'] . "&amp;pid=" . $row['id'] . "\"><img class=\"photo\" src=\"photos/member" . $row[user] . "/tb_" . $row['filename'] . "\" alt=\"\"/></a><div class=\"cat_info\">".$LANG[$monthName]."$date</div>";
		}
		if (!empty($cat_array)) {
			echo "<h4>".$LANG['latest_cat']."</h4>\n\t\t\t<div class=\"gal_row clearfix\">\n\t\t\t\t";
			foreach ($cat_array as $cat_link) {
				echo "<div class=\"cat\">$cat_link</div>";
			}
			echo "\n\t\t\t</div>\n";
		} else {
			echo "<div class=\"info-alert\"><p><h2>".$LANG['info_gal_empty1']."</h2></p><p><i>".$LANG['info_gal_empty2']."</i></p><p><b>".$LANG['info_gal_empty3']."</b><br/>".$LANG['info_gal_empty4']." <a href=\"?action=category\">".$LANG['create_edit_cat']."</a> ".$LANG['info_gal_empty5']." <a href=\"?action=upload\">".$LANG['upload_photos']."</a>.</p></div>";
		}
	}

	function displayLatestTopMost($type = 'comments', $uid = '0', $from = '-1') {
		global $LANG;
		if ($type == "comments") {
			$url = "commentpid=";
			$viewurl = "view=comments";
			$sql = "SELECT p.user AS user_cat_id, p.category, c.photo, c.date, p.filename, c.comment, c.user FROM fcms_gallery_comments AS c, fcms_gallery_photos AS p, fcms_gallery_category AS cat, fcms_users AS u WHERE c.photo = p.id AND p.category = cat.id AND c.user = u.id ORDER BY c.date DESC ";
		} elseif ($type == "toprated") {
			$url = "topratedpid=";
			$viewurl = "view=toprated&amp;u=".$_GET['u'];
			$sql = "SELECT `user`, `filename`, `rating`/`votes` AS 'r' FROM `fcms_gallery_photos` WHERE `votes`>0 ";
			if ($uid > 0) { $sql .= "AND `user` = $uid "; }
			$sql .= "ORDER BY r DESC ";
		} elseif ($type == "views") {
			$url = "viewspid=";
			$viewurl = "view=views&amp;u=".$_GET['u'];
			$sql = "SELECT `user`, `filename`, `views` FROM `fcms_gallery_photos` WHERE `views` > 0 ";
			if ($uid > 0) { $sql .= "AND `user` = $uid "; }
			$sql .= "ORDER BY `views` DESC ";
		}
		if ($from >= 0) { $sql .= "LIMIT $from, 16"; } else { $sql .= "LIMIT 8"; }
		$this->db->query($sql) or die('<h1>Top/Most Error (gallery.class.php 61)</h1>' . mysql_error());
		while ($row = $this->db->get_row()) {
			if ($type == "comments") {
				$com = $row['comment'];
				if(strlen($com) > 25) { $com = substr($com, 0, 22) . "..."; }
				$monthName = date('M', strtotime($row['date']));
				$date = date('. j, Y', strtotime($row['date']));
				$displayname = getUserDisplayName($row['user']);
				$photo_array[] = "<div class=\"cat_name\">".$LANG[$monthName]."$date</div><a href=\"?$url" . $row['filename'] . "\"><img class=\"photo\" src=\"photos/member" . $row['user_cat_id'] . "/tb_" . $row['filename'] . "\" alt=\"\"/></a><div class=\"cat_info\"><b>$displayname</b>: $com</div>";
			} elseif ($type == "toprated") {
				$width = ($row['r'] / 5) * 100;
				$photo_array[] = "<a href=\"?$url" . $row['filename'] . "\"><img class=\"photo\" src=\"photos/member" . $row['user'] . "/tb_" . $row['filename'] . "\" alt=\"\"/></a><br/><ul class=\"star-rating small-star\"><li class=\"current-rating\" style=\"width:$width%\">Currently " . $row['r'] . "/5 Stars.</li><li><a href=\"?$url" . $row['filename'] . "&amp;vote=1\" title=\"".$LANG['title_stars1']."\" class=\"one-star\">1</a></li><li><a href=\"?$url" . $row['filename'] . "&amp;vote=2\" title=\"".$LANG['title_stars2']."\" class=\"two-stars\">2</a></li><li><a href=\"?$url" . $row['filename'] . "&amp;vote=3\" title=\"".$LANG['title_stars3']."\" class=\"three-stars\">3</a></li><li><a href=\"?$url" . $row['filename'] . "&amp;vote=4\" title=\"".$LANG['title_stars4']."\" class=\"four-stars\">4</a></li><li><a href=\"?$url" . $row['filename'] . "&amp;vote=5\" title=\"".$LANG['title_stars5']."\" class=\"five-stars\">5</a></li></ul>";
			} elseif ($type == "views") {
				$photo_array[] = "<a href=\"?$url" . $row['filename'] . "\"><img class=\"photo\" src=\"photos/member" . $row['user'] . "/tb_" . $row['filename'] . "\" alt=\"\"/></a><br/><small><b>".$LANG['views'].": </b>" . $row['views'] . "</small>";
			}
		}
		if ($type != "comments") {
			echo "\t\t\t<h4"; 
			if ($from < 0) { echo " class=\"fleft\""; } 
			if ($type == "toprated") { echo ">".$LANG['top_rated']."</h4>"; } elseif ($type == "views") { echo ">".$LANG['most_viewed']."</h4>"; }
			if ($from < 0) { echo "<a style=\"float: left; margin-left: 5px;\" href=\"?$viewurl\">(".$LANG['view_all'].")</a>"; }
		}
		if (!empty($photo_array)) {
			if ($type == "comments") { 
				echo "\t\t\t<h4"; 
				if ($from < 0) { echo " class=\"fleft\""; } 
				echo ">".$LANG['latest_comments']."</h4>";
				if ($from < 0) { echo "<a style=\"float: left; margin-left: 5px;\" href=\"?$viewurl\">(".$LANG['view_all'].")</a>"; }
			}
			echo "\n\t\t\t<div class=\"gal_row clearfix\">\n\t\t\t\t";
			$i = 0;
			foreach ($photo_array as $photo_link) {
				if ($i == $this->categories_per_row) {	
					$i = 1;
					echo "</div>\n\t\t\t<div class=\"gal_row clearfix\">\n\t\t\t\t";
				} else {
					$i++;
				}
				echo "<div class=\"cat\">$photo_link</div>";
			}
			echo "\n\t\t\t</div>\n";
			if ($from >= 0) {
				$page = ($from / 16) + 1; $sql = "";
				if ($type == "comments") {
					$sql = "SELECT * FROM `fcms_gallery_comments`";
				} elseif ($type == "toprated") {
					$sql = "SELECT * FROM `fcms_gallery_photos` WHERE `rating` > 0";  if ($uid > 0) { $sql .= " AND `user` = $uid"; }
				} elseif ($type == "views") {
					$sql = "SELECT * FROM `fcms_gallery_photos` WHERE `views` > 0";  if ($uid > 0) { $sql .= " AND `user` = $uid"; }
				}
				$this->db->query($sql) or die('<h1>Count Error (gallery.class.php 116)</h1>' . mysql_error());
				$count = $this->db->count_rows();
				$total_pages = ceil($count / 16); 
				if ($total_pages > 1) {
					echo "\t\t\t<div class=\"pages clearfix\"><ul>"; 
					if ($page > 1) { 
						$prev = ($page - 1); 
						echo "<li><a title=\"".$LANG['title_first_page']."\" class=\"first\" href=\"?$viewurl&amp;page=1\"></a></li>"; 
						echo "<li><a title=\"".$LANG['title_prev_page']."\" class=\"previous\" href=\"?$viewurl&amp;page=$prev\"></a></li>"; 
					} 
					if ($total_pages > 8) {
						if ($page > 2) {
							for ($i = ($page-2); $i <= ($page+5); $i++) {
								if ($i <= $total_pages) { echo "<li><a href=\"?$viewurl&amp;page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>"; }
							} 
						} else {
							for ($i = 1; $i <= 8; $i++) { echo "<li><a href=\"?$viewurl&amp;page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>"; } 
						}
					} else {
						for ($i = 1; $i <= $total_pages; $i++) {
							echo "<li><a href=\"?$viewurl&amp;page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>";
						} 
					}
					if ($page < $total_pages) { 
						$next = ($page + 1); 
						echo "<li><a title=\"".$LANG['title_next_page']."\" class=\"next\" href=\"?$viewurl&amp;page=$next\"></a></li>"; 
						echo "<li><a title=\"".$LANG['title_last_page']."\" class=\"last\" href=\"?$viewurl&amp;page=$total_pages\"></a></li>"; 
					} 
					echo "</ul></div>\n";
				}
			}
		} else {
			if ($type == "toprated") { echo "<p>".$LANG['no_photo_rated']."</p><p>&nbsp;</p>"; } 
			elseif ($type == "views") { echo "<p>".$LANG['no_photo_viewed']."</p><p>&nbsp;</p>"; }
		}
	}

	function showAllPhoto ($photo_filename, $type = "comment") {
		global $LANG;
		$this->db->query("UPDATE `fcms_gallery_photos` SET `views` = `views`+1 WHERE `filename` = '$photo_filename'") or die('<h1>+View Error (gallery.class.php 154)</h1>' . mysql_error());
		if ($type == "comment") {
			$url = "commentpid=";
			$this->db->query("SELECT DISTINCT `filename` FROM (SELECT p.`filename` FROM `fcms_gallery_comments` AS c, `fcms_gallery_photos` AS p WHERE c.photo = p.`id` ORDER BY c.`date` DESC) as z") or die('<h1>Comment Error (gallery.class.php 157)</h1>' . mysql_error());
		} elseif ($type == "toprated") {
			$url = "topratedpid=";
			$this->db->query("SELECT `filename` FROM `fcms_gallery_photos` WHERE `votes`>0 ORDER BY `rating`/`votes` DESC") or die('<h1>Top Rated Error (gallery.class.php 160)</h1>' . mysql_error());
		} elseif ($type == "views") {
			$url = "viewspid=";
			$this->db->query("SELECT `filename` FROM `fcms_gallery_photos` WHERE `views` > 0 ORDER BY `views` DESC") or die('<h1>Most Viewed Error (gallery.class.php 163)</h1>' . mysql_error());
		}
		while ($row = $this->db->get_row()) { $photo_arr[] = $row['filename']; }
		$total_photos = count($photo_arr);
		$cur = array_search($photo_filename, $photo_arr);
		if ($cur > 0) { $prev = $cur - 1; }
		if ($cur + 1 < $total_photos) { $next = $cur + 1; }
		if ((!isset($prev)) && (!isset($next))) {
			echo "<p class=\"center\">".$LANG['photo']." "; echo $cur+1; echo " ".$LANG['of']." $total_photos</p>";
		} elseif (!isset($prev)) {
			echo "<p class=\"center\">".$LANG['photo']." "; echo $cur+1; echo " ".$LANG['of']." $total_photos <a href=\"?$url" . $photo_arr[$next] . "\">>></a></p>";
		} elseif (!isset($next)) {
			echo "<p class=\"center\"><a href=\"?$url" . $photo_arr[$prev] . "\"><<</a> ".$LANG['photo']." "; echo $cur+1; echo " ".$LANG['of']." $total_photos</p>";
		} else {
			echo "<p class=\"center\"><a href=\"?$url" . $photo_arr[$prev] . "\"><<</a> ".$LANG['photo']." "; echo $cur+1; echo " ".$LANG['of']." $total_photos <a href=\"?$url" . $photo_arr[$next] . "\">>></a></p>";
		}
		$this->db->query("SELECT `caption`, `user`, `views`, `votes`, `rating` FROM `fcms_gallery_photos` WHERE `filename` = '$photo_filename'") or die('<h1>Photo Error (gallery.class.php 179)</h1>' . mysql_error());
		$row = $this->db->get_row();
		echo "<p class=\"center\"><a href=\"photos/member" . $row['user'] . "/$photo_filename\"><img class=\"photo\" src=\"photos/member" . $row['user'] . "/$photo_filename\" alt=\"".htmlentities($row['caption'])."\" /></a></p><div class=\"comment_block\"><p class=\"center\">".htmlentities($row['caption'])."</p></div>";
		if ($row['votes'] <= 0) { $rating = 0; $width = 0; } else { $rating = ($row['rating'] / $row['votes']) * 100; $width = $rating / 5; }
		echo "<ul class=\"star-rating small-star\"><li class=\"current-rating\" style=\"width:$width%\">Currently " . $row['rating'] . "/5 Stars.</li><li><a href=\"?$url" . "$photo_filename&amp;vote=1\" title=\"".$LANG['title_stars1']."\" class=\"one-star\">1</a></li><li><a href=\"?$url" . "$photo_filename&amp;vote=2\" title=\"".$LANG['title_stars2']."\" class=\"two-stars\">2</a></li><li><a href=\"?$url" . "$photo_filename&amp;vote=3\" title=\"".$LANG['title_stars3']."\" class=\"three-stars\">3</a></li><li><a href=\"?$url" . "$photo_filename&amp;vote=4\" title=\"".$LANG['title_stars4']."\" class=\"four-stars\">4</a></li><li><a href=\"?$url" . "$photo_filename&amp;vote=5\" title=\"".$LANG['title_stars5']."\" class=\"five-stars\">5</a></li></ul>";
		echo "<p class=\"center\"><small>".$LANG['views'].": " . $row['views'] . "</small></p>";
		if (checkAccess($_SESSION['login_id']) <= 8 && checkAccess($_SESSION['login_id']) != 7 && checkAccess($_SESSION['login_id']) != 4) {
			echo "<p class=\"center\">&nbsp;</p>\n\t\t\t<h3>".$LANG['comments']."</h3><p class=\"center\"><form action=\"?$url" . "$photo_filename\" method=\"post\">".$LANG['add_comment']."<br/><input type=\"text\" name=\"comment\" id=\"comment\" size=\"50\" title=\"".$LANG['add_comment']."\"/> <input type=\"submit\" name=\"addcom\" id=\"addcom\" value=\" \" class=\"gal_addcombtn\" /></form></p><p class=\"center\">&nbsp;</p>";
			$photo = substr($photo_filename, 0, strpos($photo_filename, '.'));
			$this->db->query("SELECT c.`id`, `comment`, `date`, `fname`, `lname`, `displayname`, `username`, `user` FROM `fcms_gallery_comments` AS c, `fcms_users` AS u WHERE `photo` = '$photo' AND c.`user` = u.`id` ORDER BY `date`") or die('<h1>Comments Error (gallery.class.php 187)</h1>' . mysql_error());
			if ($this->db->count_rows() > 0) { 
				while ($row = $this->db->get_row()) {
					$displayname = getUserDisplayName($row['user']);
					$date = fixDST(gmdate('Y-m-d h:i:s', strtotime($row['date'] . $this->tz_offset)), $this->cur_user_id, 'M. d, Y (h:i a)');
					if ($this->cur_user_id == $row['user'] || checkAccess($this->cur_user_id) < 2) {
						echo "<div class=\"comment_block\"><form action=\"?$url" . "$photo_filename\" method=\"post\"><input type=\"submit\" name=\"delcom\" id=\"delcom\" value=\" \" class=\"gal_delcombtn\" title=\"".$LANG['title_del_comment']."\" onclick=\"javascript:return confirm('".$LANG['js_del_comment']."'); \"/><span>$date</span><b>$displayname</b><br/>".htmlentities($row['comment'])."<input type=\"hidden\" name=\"id\" value=\"".$row['id']."\"></form></div>";
					} else {
						echo "<div class=\"comment_block\"><span>$date</span><b>$displayname</b><br/>".htmlentities($row['comment'])."</div>";
					}
				}
			} else {
				echo "<p class=\"center\">".$LANG['no_comments']."</p>";
			}
		}
	}

	function showPhoto ($uid, $cid, $pid) {
		global $LANG;
		$this->db->query("SELECT `caption`, `filename`, `user`, `views`, `votes`, `rating` FROM `fcms_gallery_photos` WHERE `id`=$pid") or die('<h1>Photo Error (gallery.class.php 203)</h1>' . mysql_error());
		if ($this->db->count_rows() > 0) {
			$photo = $this->db->get_row();
			$this->db->query("UPDATE `fcms_gallery_photos` SET `views` = `views`+1 WHERE `id` = $pid") or die('<h1>+View Error (gallery.class.php 206)</h1>' . mysql_error());
			$this->db->query("SELECT u.`id`, `fname`, `lname`, `displayname`, `username`, `name` AS `category` FROM `fcms_gallery_category` AS c, `fcms_users` AS u, `fcms_gallery_photos` AS p WHERE p.`category` = c.`id` AND p.`user` = u.`id` AND p.`category` = $cid LIMIT 1") or die('<h1>Category Error (gallery.class.php 207)</h1>' . mysql_error());
			$category = $this->db->get_row();
			$displayname = getUserDisplayName($category['id']);
			echo "<p><a href=\"?view=member\">".$LANG['member_gal']."</a> &gt; <a href=\"?uid=$uid\">$displayname</a> &gt; <a href=\"?uid=$uid&amp;cid=$cid\">".$category['category']."</a></p>\n";
			$this->db->query("SELECT `filename` FROM `fcms_gallery_photos` WHERE `category` = $cid ORDER BY `date`") or die('<h1>Photo Error (gallery.class.php 208)</h1>' . mysql_error());
			while ($row = $this->db->get_row()) { $photo_arr[] = $row['filename']; }
			$total_photos = count($photo_arr);
			$cur = array_search($photo['filename'], $photo_arr);
			$prev_pid = substr($photo_arr[$cur-1], 0, strpos($photo_arr[$cur-1], '.'));
			$next_pid = substr($photo_arr[$cur+1], 0, strpos($photo_arr[$cur+1], '.'));
			if (empty($prev_pid) && empty($next_pid)) {
				echo "<p class=\"center\">".$LANG['photo']." "; echo $cur+1; echo " ".$LANG['of']." $total_photos</p>";
			} elseif (empty($prev_pid)) {
				echo "<p class=\"center\">".$LANG['photo']." "; echo $cur+1; echo " ".$LANG['of']." $total_photos <a href=\"?uid=$uid&amp;cid=$cid&amp;pid=$next_pid\">>></a></p>";
			} elseif (empty($next_pid)) {
				echo "<p class=\"center\"><a href=\"?uid=$uid&amp;cid=$cid&amp;pid=$prev_pid\"><<</a> ".$LANG['photo']." "; echo $cur+1; echo " ".$LANG['of']." $total_photos</p>";
			} else {
				echo "<p class=\"center\"><a href=\"?uid=$uid&amp;cid=$cid&amp;pid=$prev_pid\"><<</a> ".$LANG['photo']." "; echo $cur+1; echo " ".$LANG['of']." $total_photos <a href=\"?uid=$uid&amp;cid=$cid&amp;pid=$next_pid\">>></a></p>";
			}
			if($this->cur_user_id == $photo['user'] || checkAccess($this->cur_user_id) < 2) {
				echo "<div class=\"edit_del_photo\"><form action=\"index.php\" method=\"post\"><div><input type=\"hidden\" name=\"photo\" id=\"photo\" value=\"$pid\"/>".$LANG['edit']." <input type=\"submit\" name=\"editphoto\" id=\"editphoto\" value=\" \" class=\"gal_editbtn\" /></div></form>&nbsp;&nbsp;<form action=\"index.php\" method=\"post\"><div><input type=\"hidden\" name=\"photo\" id=\"photo\" value=\"$pid\"/>".$LANG['delete']." <input type=\"submit\" name=\"deletephoto\" id=\"addcom\" value=\" \" class=\"gal_delbtn\" onclick=\"javascript:return confirm('".$LANG['js_del_photo']."'); \"/></div></form></div>";
			}
			echo "<p class=\"center\"><a href=\"photos/member" . $photo['user'] . "/" . $photo['filename'] . "\"><img class=\"photo\" src=\"photos/member" . $photo['user'] . "/" . $photo['filename'] . "\" alt=\"".htmlentities($photo['caption'])."\" /></a></p><div class=\"comment_block\"><p class=\"center\">".htmlentities($photo['caption'])."</p></div>";
			if ($photo['votes'] <= 0) { $rating = 0; $width = 0; } else { $rating = ($photo['rating'] / $photo['votes']) * 100; $width = $rating / 5; }
			echo "<ul class=\"star-rating small-star\"><li class=\"current-rating\" style=\"width:$width%\">Currently " . $photo['rating'] . "/5 Stars.</li><li><a href=\"?uid=$uid&amp;cid=$cid&amp;pid=$pid&amp;vote=1\" title=\"".$LANG['title_stars1']."\" class=\"one-star\">1</a></li><li><a href=\"?uid=$uid&amp;cid=$cid&amp;pid=$pid&amp;vote=2\" title=\"".$LANG['title_stars2']."\" class=\"two-stars\">2</a></li><li><a href=\"?uid=$uid&amp;cid=$cid&amp;pid=$pid&amp;vote=3\" title=\"".$LANG['title_stars3']."\" class=\"three-stars\">3</a></li><li><a href=\"?uid=$uid&amp;cid=$cid&amp;pid=$pid&amp;vote=4\" title=\"".$LANG['title_stars4']."\" class=\"four-stars\">4</a></li><li><a href=\"?uid=$uid&amp;cid=$cid&amp;pid=$pid&amp;vote=5\" title=\"".$LANG['title_stars5']."\" class=\"five-stars\">5</a></li></ul>";
			echo "<p class=\"center\"><small>".$LANG['views'].": " . $photo['views'] . "</small></p>";
			if (checkAccess($_SESSION['login_id']) <= 8 && checkAccess($_SESSION['login_id']) != 7 && checkAccess($_SESSION['login_id']) != 4) {
				echo "<p class=\"center\">&nbsp;</p>\n\t\t\t<h3>".$LANG['comments']."</h3><p class=\"center\"><form action=\"?uid=$uid&amp;cid=$cid&amp;pid=$pid\" method=\"post\">".$LANG['add_comment']."<br/><input type=\"text\" name=\"comment\" id=\"comment\" size=\"50\" title=\"".$LANG['add_comment']."\"/> <input type=\"submit\" name=\"addcom\" id=\"addcom\" value=\" \" class=\"gal_addcombtn\" /></form></p><p class=\"center\">&nbsp;</p>";
				$this->db->query("SELECT c.`id`, `comment`, `date`, `fname`, `lname`, `displayname`, `username`, `user` FROM `fcms_gallery_comments` AS c, `fcms_users` AS u WHERE `photo` = '$pid' AND c.`user` = u.`id` ORDER BY `date`") or die('<h1>Comments Error (gallery.class.php 234)</h1>' . mysql_error());
				if ($this->db->count_rows() > 0) { 
					while ($row = $this->db->get_row()) {
						$displayname = getUserDisplayName($row['user']);
						$date = fixDST(gmdate('Y-m-d h:i:s', strtotime($row['date'] . $this->tz_offset)), $this->cur_user_id, 'M. d, Y (h:i a)');
						if ($this->cur_user_id == $row['user'] || checkAccess($this->cur_user_id) < 2) {
							echo "<div class=\"comment_block\"><form action=\"?page=photo&amp;uid=$uid&amp;cid=$cid&amp;pid=$pid\" method=\"post\"><input type=\"submit\" name=\"delcom\" id=\"delcom\" value=\" \" class=\"gal_delcombtn\" title=\"".$LANG['title_del_comment']."\" onclick=\"javascript:return confirm('".$LANG['js_del_comment']."'); \"/><span>$date</span><b>$displayname</b><br/>".htmlentities($row['comment'])."<input type=\"hidden\" name=\"id\" value=\"".$row['id']."\"></form></div>";
						} else {
							echo "<div class=\"comment_block\"><span>$date</span><b>$displayname</b><br/>".htmlentities($row['comment'])."</div>";
						}
					}
				} else {
					echo "<p class=\"center\">".$LANG['no_comments']."</p>";
				}
			}
		} else {
			echo "<p class=\"error-alert\">".$LANG['err_photo_not_found']."</p>";
		}
	}

	function displayUploadForm($num, $last_cat) {
		global $LANG;
		echo "<h3>".$LANG['upload_photos']."</h3><p style=\"width: 90%; text-align: right;\"><a class=\"help\" href=\"../help.php#gallery-howworks\">".$LANG['link_help']."</a></p>";
		echo "<script src=\"../inc/prototype.js\" type=\"text/javascript\"></script><form enctype=\"multipart/form-data\" action=\"?action=upload\" method=\"post\"><p>".$LANG['select_cat']." <select name=\"category\">";
		$this->db->query("SELECT `id`, `name` FROM `fcms_gallery_category` WHERE user=$this->cur_user_id") or die('<h1>Category Error (gallery.class.php 253)</h1>' . mysql_error());
		while($row = $this->db->get_row()) { echo "<option ";  if ($last_cat == $row['id']) { echo "selected=\"selected\" "; } echo "value=\"" . $row['id'] . "\">".$row['name']."</option>"; }
		echo "</select></p><br/>";
		$i = 1;
		while($i <= $num) {
			if ($num > 1) { echo "<p>".$LANG['photo']." ($i):"; } else { echo "<p>".$LANG['photo'].":"; }
			echo " <input name=\"photo_filename[]\" type=\"file\" size=\"50\"/>";
			if ($num <= 1) { echo "<br/><a href=\"#\" onclick=\"$('upload-options$i').toggle(); return false\">".$LANG['upload_options']."</a>"; }
			echo "</p>";
			if ($num <= 1) { echo "<div id=\"upload-options$i\" style=\"display:none;\"><p>".$LANG['rotate_left'].": <input type=\"radio\" name=\"rotate[]\" value=\"left\"/>&nbsp;&nbsp; ".$LANG['rotate_right'].": <input type=\"radio\" name=\"rotate[]\" value=\"right\"/></p></div>"; }
			echo "<p>".$LANG['caption'].": <input type=\"text\" name=\"photo_caption[]\" size=\"50\"/></p>";
			if ($num > 1) { echo "<p>&nbsp;</p>"; }
			$i++;
		}
		echo "<br/><input type=\"submit\" id=\"addphoto\" name=\"addphoto\" value=\"".$LANG['submit']."\" /></form><p>&nbsp;</p>";
	}

	function displayEditPhotoForm ($photo) {
		global $LANG;
		echo "<h4>".$LANG['edit_photo']."</h4>";
		$this->db->query("SELECT p.user, filename, caption, name FROM fcms_gallery_photos AS p, fcms_gallery_category AS c WHERE p.id=$photo AND p.category = c.id") or die('<h1>Photo Error (gallery.class.php 274)</h1>' . mysql_error());
		if ($this->db->count_rows() > 0) {
			$row = $this->db->get_row();
			$photo_user = $row['user'];
			$filename = $row['filename'];
			$caption = $row['caption'];
			$category = $row['name'];
			echo "<script src=\"inc/prototype.js\" type=\"text/javascript\"></script><form action=\"?page=photo\" method=\"post\">".$LANG['change_cat']." <select name=\"category\">";
			$this->db->query("SELECT id,name FROM fcms_gallery_category WHERE user=$photo_user") or die('<h1>Category Error (gallery.class.php 281)</h1>' . mysql_error());
			while($row = $this->db->get_row()) {
				echo "<option value=\"$row[0]\""; if($category == $row[1]) { echo " selected=\"selected\""; } echo ">$row[1]</option>";
			}
			echo "</select><p><img src='photos/member$photo_user/$filename'/></p>";
			echo "<p>".$LANG['caption'].": <input type=\"text\" name=\"photo_caption\" size=\"50\" value=\"$caption\"/></p>";
			echo "<input type=\"hidden\" name=\"photo_id\" id=\"photo_id\" value=\"$photo\"/><input type=\"submit\" name=\"add_editphoto\" value=\"".$LANG['submit_changes']."\" /></form>";
		} else {
			echo "<p class=\"error-alert\">".$LANG['err_edit_photo']."</p>";
		}
	}

	function uploadPhoto ($category, $files, $captions, $rotateoptions) {
		global $LANG;
		$known_photo_types = array('image/pjpeg' => 'jpg', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/bmp' => 'bmp', 'image/x-png' => 'png');
		$gd_function_suffix = array('image/pjpeg' => 'JPEG', 'image/jpeg' => 'JPEG', 'image/gif' => 'GIF', 'image/bmp' => 'WBMP', 'image/x-png' => 'PNG');
		$photos_uploaded = $files;
		$photos_caption = $captions;
		$i = 0;
		while ($i < count($photos_uploaded['name'])) {
			if ($photos_uploaded['size'][$i] > 0) {
				if (!array_key_exists($photos_uploaded['type'][$i], $known_photo_types)) {
					echo "<p class=\"error-alert\">".$LANG['err_not_file1']." ".($i+1)." ".$LANG['err_not_file2']."</p><br />";
				} else {
					$this->db->query("INSERT INTO `fcms_gallery_photos`(`date`, `caption`, `category`, `user`) VALUES(NOW(), '".addslashes($photos_caption[$i])."', '" . addslashes($category) . "', $this->cur_user_id)") or die('<h1>New Photo Error (gallery.class.php 305)</h1>' . mysql_error());
					$new_id = mysql_insert_id();
					$filetype = $photos_uploaded['type'][$i];
					$extention = $known_photo_types[$filetype];
					$filename = $new_id . "." . $extention;
					$this->db->query("UPDATE `fcms_gallery_photos` SET `filename`='" . addslashes($filename) . "' WHERE id='" . addslashes($new_id) . "'") or die('<h1>Photo Error (gallery.class.php 310)</h1>' . mysql_error());
					if (!file_exists("photos/member" . $this->cur_user_id)) {
						mkdir("photos/member" . $this->cur_user_id);
					}
					copy($photos_uploaded['tmp_name'][$i], "photos/member" . $this->cur_user_id . "/" . $filename);
					$size = GetImageSize("photos/member" . $this->cur_user_id . "/" . $filename);
					if ($size[0] > $size[1]) {
						if ($size[0] > 100) { $thumbnail_width = 100; $thumbnail_height = (int)(100 * $size[1] / $size[0]); } else { $thumbnail_width = $size[0]; $thumbnail_height = $size[1]; }
					} else {
						if ($size[1] > 100) { $thumbnail_width = (int)(100 * $size[0] / $size[1]); $thumbnail_height = 100; } else { $thumbnail_width = $size[0]; $thumbnail_height = $size[1]; }
					}
					if ($size[0] > 500) { $main_width = 500; $main_height = (int)(500 * $size[1] / $size[0]); } else { $main_width = $size[0]; $main_height = $size[1]; }
					$function_suffix = $gd_function_suffix[$filetype];
					$function_to_read = "ImageCreateFrom" . $function_suffix;
					$function_to_write = "Image" . $function_suffix;
					$source_handle = $function_to_read("photos/member" . $this->cur_user_id . "/" . $filename); 
					if($source_handle) {
						$thumb_destination_handle = ImageCreateTrueColor($thumbnail_width, $thumbnail_height);
						ImageCopyResampled($thumb_destination_handle, $source_handle, 0, 0, 0, 0, $thumbnail_width, $thumbnail_height, $size[0], $size[1]);
						$main_destination_handle = ImageCreateTrueColor($main_width, $main_height);
						ImageCopyResampled($main_destination_handle, $source_handle, 0, 0, 0, 0, $main_width, $main_height, $size[0], $size[1]);
					}
					$function_to_write($thumb_destination_handle, "photos/member" . $this->cur_user_id . "/tb_" . $filename);
					$function_to_write($main_destination_handle, "photos/member" . $this->cur_user_id . "/" . $filename);
					// File Rotation
					if ($rotateoptions[$i] == 'left' || $rotateoptions[$i] =='right') {
						if ($rotateoptions[$i] == 'left') {
							$rotate_thumb = imagerotate($thumb_destination_handle, 90, 0);
							$rotate_main = imagerotate($main_destination_handle, 90, 0);
						}
						if ($rotateoptions[$i] == 'right') {
							$rotate_thumb = imagerotate($thumb_destination_handle, 270, 0);
							$rotate_main = imagerotate($main_destination_handle, 270, 0);
						}
						switch($function_suffix) {
							case 'JPEG': imagejpeg($rotate_thumb, "photos/member" . $this->cur_user_id . "/tb_" . $filename); imagejpeg($rotate_main, "photos/member" . $this->cur_user_id . $filename); break;
							case 'GIF': imagegif($rotate_thumb, "photos/member" . $this->cur_user_id . "/tb_" . $filename); imagegif($rotate_main, "photos/member" . $this->cur_user_id . $filename); break;
							case 'WBMP': imagewbmp($rotate_thumb, "photos/member" . $this->cur_user_id . "/tb_" . $filename); imagewbmp($rotate_main, "photos/member" . $this->cur_user_id . $filename); break;
							case 'PNG': imagepng($rotate_thumb, "photos/member" . $this->cur_user_id . "/tb_" . $filename); imagepng($rotate_main, "photos/member" . $this->cur_user_id . $filename); break;
							default: imagejpg($rotate_thumb, "photos/member" . $this->cur_user_id . "/tb_" . $filename); imagejpg($rotate_main, "photos/member" . $this->cur_user_id . $filename); break;
						}
					}
					ImageDestroy($thumb_destination_handle);
					ImageDestroy($main_destination_handle);
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
	}

	function showCategories ($uid = '0', $cid = '0') {
		global $LANG;
		if ($uid < 1 && $cid < 1) {
			$this->db->query("SELECT u.`id`, f.`filename`, COUNT(p.`id`) as c FROM `fcms_gallery_category` AS cat LEFT JOIN `fcms_gallery_photos` AS p ON p.`category` = cat.`id`, `fcms_users` AS u, (SELECT * FROM `fcms_gallery_photos` ORDER BY `date` DESC) AS f WHERE f.`id` = p.`id` AND u.`id` = p.`user` GROUP BY p.`user`") or die('<h1>Categories Error (gallery.class.php 368)</h1>' . mysql_error());
		} elseif ($cid < 1) {
			$this->db->query("SELECT u.`id`, cat.`name` AS category, cat.`id` AS cid, f.`filename`, COUNT(p.`id`) AS c FROM `fcms_gallery_category` AS cat LEFT JOIN `fcms_gallery_photos` AS p ON p.`category` = cat.`id`, `fcms_users` AS u, (SELECT * FROM `fcms_gallery_photos` ORDER BY `date` DESC) AS f WHERE f.`id` = p.`id` AND u.`id` = p.`user` AND p.`user`=$uid GROUP BY cat.`id` DESC") or die('<h1>Categories Error (gallery.class.php 370)</h1>' . mysql_error());
		} else {
			$this->db->query("SELECT u.`id`, p.`id` AS pid, `caption`, `filename` FROM `fcms_gallery_photos` AS p, `fcms_users` as u WHERE p.`user` = u.`id` AND `category`=$cid") or die('<h1>Categories Error (gallery.class.php 372)</h1>' . mysql_error());
		}
		if ($this->db->count_rows() > 0) {
			while ($row = $this->db->get_row()) {
				$displayname = getUserDisplayName($row['id']);
				if ($uid < 1 && $cid < 1) {
					$categories[] = "<div class=\"cat_name\">$displayname</div><a href=\"?uid=" . $row['id'] . "\"><img class=\"photo\" src=\"photos/member" . $row['id'] . "/tb_" . $row['filename'] . "\" alt=\"".$LANG['alt_view_cat_for']." $displayname\"/></a><div class=\"cat_info\">".$LANG['photos']." (" . $row['c'] . ")</div>";
				} elseif ($cid < 1) {
					$categories[] = "<div class=\"cat_name\">".$row['category']."</div><a href=\"?uid=$uid&amp;cid=" . $row['cid'] . "\"><img class=\"photo\" src=\"photos/member$uid/tb_" . $row['filename'] . "\" alt=\"".$LANG['alt_view_photos_in']." ".htmlentities($row['category'])."\"/></a><div class=\"cat_info\">".$LANG['photos']." (" . $row['c'] . ")</div>";
				} else {
					$categories[] = "<a href=\"?uid=$uid&amp;cid=$cid&amp;pid=" . $row['pid'] . "\"><img class=\"photo\" src=\"photos/member$uid/tb_" . $row['filename'] . "\" alt=\"".htmlentities($row['caption'])."\"/></a>";
				}
			}
			if ($uid < 1 && $cid < 1) {
				echo "<h4>".$LANG['photos']."</h4>";
			} elseif ($cid < 1) {
				echo "<p><a href=\"?view=member\">".$LANG['member_gal']."</a> &gt; $displayname</p>\n";
			} else {
				$this->db2->query("SELECT `name` FROM `fcms_gallery_category` AS c, `fcms_gallery_photos` AS p WHERE p.`category` = c.`id` AND p.`category`=$cid LIMIT 1");
				$row = $this->db2->get_row();
				echo "<p><a href=\"?view=member\">".$LANG['member_gal']."</a> &gt; <a href=\"?uid=$uid\">$displayname</a> &gt; ".$row['name']."</p>\n";
			}
			echo "\t\t\t<div class=\"gal_row clearfix\">";
			$i = 0;
			foreach ($categories as $category_block) {
				if ($i == $this->categories_per_row) {	
					$i = 1;
					echo "</div>\n\t\t\t<div class=\"gal_row clearfix\">";
				} else { $i++; }
				echo "<div class=\"cat\">" . $category_block . "</div>";
			}
			echo "</div>";
		} else {
			echo "<div class=\"info-alert\"><h2>".$LANG['info_cat_empty1']."</h2><p>".$LANG['info_cat_empty2']."</p></div>";
		}
	}

	function displayAddCatForm () {
		global $LANG;
		echo "<h3>".$LANG['create_cat']."</h3>";
		echo "<form action=\"index.php?action=category\" method=\"post\">".$LANG['new_cat_name'].": <input type=\"text\" name=\"cat_name\" id=\"cat_name\" /><input type=\"submit\" name=\"newcat\" id=\"newcat\" value=\"".$LANG['add_cat']."\" /></form>";
		echo "\t<p>&nbsp;</p><h3>".$LANG['edit_cat']."</h3>\n\t\t<ul class=\"gallery_cat\">\n\t\t";
		$this->db->query("SELECT * FROM fcms_gallery_category WHERE user=" . $this->cur_user_id) or die('<h1>Category Error (gallery.class.php 413)</h1>' . mysql_error());
		if ($this->db->count_rows() > 0) {
			while ($row = $this->db->get_row()) {
				echo "<li><form action=\"index.php?action=category\" method=\"post\"><input type=\"text\" name=\"cat_name\" id=\"cat_name\" size=\"60\" value=\"".htmlentities($row['name'])."\"/><input type=\"hidden\" name=\"cid\" id=\"cid\" value=\"" . $row['id'] . "\"/> &nbsp;<input type=\"submit\" name=\"editcat\" class=\"editbtn\" value=\" \"/></form> &nbsp;";
				echo "<form action=\"index.php?action=category\" method=\"post\"><input type=\"hidden\" name=\"cid\" id=\"cid\" value=\"" . $row['id'] . "\"/><input type=\"submit\" name=\"delcat\" class=\"delbtn\" value=\" \" onclick=\"javascript:return confirm('".$LANG['js_del_cat']."'); \"/></form></li>\n\t\t";
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
		$this->db->query("SELECT DISTINCT p.user, name AS category, p.category AS cid, DAYOFYEAR(`date`) AS d, COUNT(*) AS c FROM fcms_gallery_photos AS p, fcms_users AS u, fcms_gallery_category AS c WHERE p.user = u.id AND p.category = c.id AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) GROUP BY user, category, d ORDER BY `date` DESC LIMIT 0 , 5") or die('<h1>New Error (gallery.class.php 425)</h1>' . mysql_error());
		if ($this->db->count_rows() > 0) {
			echo "\t\t\t\t<ul class=\"twolines\">\n";
			while($row = $this->db->get_row()) {
				$displayname = getUserDisplayName($row['user']);
				$category = $row['category'];
				$full_category = $row['category'];
				if (strlen($category) > 20) { $category = substr($category, 0, 17) . "..."; }
				$this->db2->query("SELECT `date` FROM fcms_gallery_photos AS p, fcms_gallery_category AS c WHERE p.user = " . $row['user'] . " AND c.id = p.category AND c.name = '" . addslashes($full_category) . "' AND DAYOFYEAR(p.`date`) = " . $row['d'] . " ORDER BY `date` DESC LIMIT 1") or die('<h1>Date Error (gallery.class.php 440)</h1>' . mysql_error());
				$found = $this->db2->get_row();
				$monthName = gmdate('M', strtotime($found['date'] . $this->tz_offset));
				$date = gmdate('. j, Y, g:i a', strtotime($found['date'] . $this->tz_offset));
				echo "\t\t\t\t\t<li";
				if (strtotime($date) >= strtotime($today) && strtotime($date) > $tomorrow) { echo " class=\"new\""; }
				echo "><a href=\"gallery/index.php?uid=" . $row['user'] . "&amp;cid=" . $row['cid'] . "\" title=\"" . htmlentities($full_category) . "\">$category</a> - " . $row['c'] . " ".$LANG['new_photos']."<br/><span>".$LANG[$monthName]."$date - <a class=\"u\" href=\"profile.php?member=" . $row['user'] . "\">$displayname</a></span></li>\n";			
			}
			echo "\t\t\t\t</ul>\n";
		} else {
			echo "\t\t\t\t<ul><li><i>".$LANG['nothing_new_30']."</i></li></ul>\n";
		}
	}

} ?>