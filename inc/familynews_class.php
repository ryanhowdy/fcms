<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

class FamilyNews {

	var $db;
	var $db2;
	var $tz_offset;
	var $cur_user_id;

	function FamilyNews ($current_user_id, $type, $host, $database, $user, $pass) {
		$this->cur_user_id = $current_user_id;
		$this->db = new database($type, $host, $database, $user, $pass);
		$this->db2 = new database($type, $host, $database, $user, $pass);
		$this->db->query("SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $current_user_id") or die('<h1>Timezone Error (familynews.class.php 16)</h1>' . mysql_error());
		$row = $this->db->get_row();
		$this->tz_offset = $row['timezone'];
	}

	function showFamilyNews ($usersnews, $id = '0', $page = '1') {
		global $LANG;
		$from = (($page * 5) - 5); 
		if ($id <= 0) {
			$sql = "SELECT n.`id`, `title`, `news`, `date` FROM `fcms_news` AS n, `fcms_users` AS u WHERE `user` = $usersnews AND `user` = u.`id` ORDER BY `date` DESC LIMIT " . $from . ", 5";
		} else {
			$sql = "SELECT n.`id`, `title`, `news`, `date` FROM `fcms_news` AS n, `fcms_users` AS u WHERE n.`id` = $id AND `user` = u.`id`";
		}
		$this->db->query($sql) or die('<h1>News Error (familynews.class.php 30)</h1>' . mysql_error());
		while ($row = $this->db->get_row()) {
			$monthName = fixDST(gmdate('F j, Y g:i a', strtotime($row['date'] . $this->tz_offset)), $this->cur_user_id, 'F');
			$date = fixDST(gmdate('F j, Y g:i a', strtotime($row['date'] . $this->tz_offset)), $this->cur_user_id, 'j, Y g:i a');
			$displayname = getUserDisplayName($usersnews);
			echo "<h2><a href=\"?getnews=$usersnews&amp;newsid=".$row['id']."\">".$row['title']."</a>";
			if ($_SESSION['login_id'] == $usersnews || checkAccess($_SESSION['login_id']) < 2) {
				echo " &nbsp;<form method=\"post\" action=\"familynews.php\"><div><input type=\"hidden\" name=\"user\" value=\"$usersnews\"/><input type=\"hidden\" name=\"id\" value=\"" . $row['id'] . "\"/><input type=\"hidden\" name=\"title\" value=\"".htmlentities($row['title'], ENT_COMPAT, 'UTF-8')."\"/><input type=\"hidden\" name=\"news\" value=\"".htmlentities($row['news'], ENT_COMPAT, 'UTF-8')."\"/><input type=\"submit\" name=\"editnews\" value=\" \" class=\"editbtn\" title=\"".$LANG['title_edit_news']."\"/></div></form>";
			}
			if (checkAccess($_SESSION['login_id']) < 2) {
				echo " &nbsp;<form method=\"post\" action=\"familynews.php\"><div><input type=\"hidden\" name=\"user\" value=\"$usersnews\"/><input type=\"hidden\" name=\"id\" value=\"" . $row['id'] . "\"/><input type=\"submit\" name=\"delnews\" value=\" \" class=\"delbtn\" title=\"".$LANG['title_delete_news']."\" onclick=\"javascript:return confirm('".$LANG['js_delete_news']."');\"/></div></form>";
			}
			echo "</h2><b>" . getLangMonthName($monthName) . " $date</b><span> - $displayname</span><p>";
			if ($id <= 0) {
				parse(rtrim(substr($row['news'], 0, 300)), 1);
				if (strlen($row['news']) > 300) { echo "...<br/><br/><a href=\"?getnews=$usersnews&amp;newsid=" . $row['id'] . "\">".$LANG['read_more']."</a>"; }
				echo "</p><p>&nbsp;</p><p style=\"text-align: right\"><a href=\"#\" onclick=\"window.open('inc/familynews_comments.php?newsid=" . $row['id'] . "', '_Comments', 'height=400,width=550,resizable=yes,scrollbars=yes');return false;\">".$LANG['comments']."</a> - " . getNewsComments($row['id']) . "</p>\n\t\t\t";
			} else {
				parse($row['news'], 1);
				echo "<p>&nbsp;</p><h3>".$LANG['comments']."</h3><p class=\"center\"><form action=\"?getnews=$usersnews&amp;newsid=$id\" method=\"post\">".$LANG['add_comment']."<br/><input type=\"text\" name=\"comment\" id=\"comment\" size=\"50\" title=\"".$LANG['add_comment']."\"/> <input type=\"submit\" name=\"addcom\" id=\"addcom\" value=\" \" class=\"gal_addcombtn\" /></form></p><p class=\"center\">&nbsp;</p>";
				$this->db2->query("SELECT c.id, comment, `date`, fname, lname, username, user FROM fcms_news_comments AS c, fcms_users AS u WHERE news = $id AND c.user = u.id ORDER BY `date`") or die('<h1>News Comments Error (familynews.class.php 51)</h1>' . mysql_error());
				if ($this->db->count_rows() > 0) { 
					while($row = $this->db2->get_row()) {
						$displayname = getUserDisplayName($row['user']);
						if ($this->cur_user_id == $row['user'] || checkAccess($this->cur_user_id) < 2) {
							echo "<div class=\"comment_block\"><form action=\"?getnews=$usersnews&amp;newsid=$id\" method=\"post\"><input type=\"submit\" name=\"delcom\" id=\"delcom\" value=\" \" class=\"gal_delcombtn\" title=\"".$LANG['title_del_comment']."\" onclick=\"javascript:return confirm('".$LANG['js_del_comment']."'); \"/><span>".$row['date']."</span><b>$displayname</b><br/>" . htmlentities(stripslashes($row['comment']), ENT_COMPAT, 'UTF-8') . "<input type=\"hidden\" name=\"id\" value=\"".$row['id']."\"></form></div>";
						} else {
							echo "<div class=\"comment_block\"><span>".$row['date']."</span><b>$displayname</b><br/>" . htmlentities(stripslashes($row['comment']), ENT_COMPAT, 'UTF-8') . "</div>";
						}
					}
				} else {
					echo "<p class=\"center\">".$LANG['no_comments']."</p>";
				}
			}
		}
		if ($id <= 0) {
			$this->db2->query("SELECT count(id) AS c FROM fcms_news WHERE user = $usersnews")  or die('<h1>Count Error (familynews.class.php 53)</h1>' . mysql_error());
			while ($r=$this->db2->get_row()) { $newscount = $r['c']; }
			$total_pages = ceil($newscount / 5); 
			if ($total_pages > 1) {
				echo "<div class=\"pages clearfix\"><ul>"; 
				if ($page > 1) { 
					$prev = ($page - 1); 
					echo "<li><a title=\"".$LANG['title_first_page']."\" class=\"first\" href=\"familynews.php?getnews=$usersnews&amp;newspage=1\"></a></li>"; 
					echo "<li><a title=\"".$LANG['title_prev_page']."\" class=\"previous\" href=\"familynews.php?getnews=$usersnews&amp;newspage=$prev\"></a></li>"; 
				} 
				if ($total_pages > 8) {
					if($page > 2) {
						for($i = ($page-2); $i <= ($page+5); $i++) {
							if($i <= $total_pages) { echo "<li><a href=\"familynews.php?getnews=$usersnews&amp;newspage=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>"; }
						} 
					} else {
						for($i = 1; $i <= 8; $i++) { echo "<li><a href=\"familynews.php?getnews=$usersnews&amp;newspage=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>"; } 
					}
				} else {
					for($i = 1; $i <= $total_pages; $i++) {
						echo "<li><a href=\"familynews.php?getnews=$usersnews&amp;newspage=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>";
					} 
				}
				if ($page < $total_pages) { 
					$next = ($page + 1); 
					echo "<li><a title=\"".$LANG['title_next_page']."\" class=\"next\" href=\"familynews.php?getnews=$usersnews&amp;newspage=$next\"></a></li>"; 
					echo "<li><a title=\"".$LANG['title_last_page']."\" class=\"last\" href=\"familynews.php?getnews=$usersnews&amp;newspage=$total_pages\"></a></li>"; 
				} 
				echo "</ul></div>";
			}
		}
		echo "<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>\n";
	}

	function displayForm ($type, $user = '0', $newsid = '0', $title='error', $news = 'error') {
		global $LANG;
		echo "<br/><script src=\"inc/prototype.js\" type=\"text/javascript\"></script>\n\t\t\t<script type=\"text/javascript\" src=\"inc/livevalidation.js\"></script>\n";
		echo "\t\t\t<script type=\"text/javascript\" src=\"inc/messageboard.inc.js\"></script>\n";
		if ($type == 'edit') {
			echo "\t\t\t<form method=\"post\" id=\"editform\" action=\"familynews.php\">\n\t\t\t\t<h3>".$LANG['edit_news']."</h3>\n";			
		} else {
			echo "\t\t\t<form method=\"post\" id=\"addform\" action=\"familynews.php\">\n\t\t\t\t<h3>".$LANG['add_news']."</h3>\n";
		}
		echo "\t\t\t\t<div><label for=\"title\">".$LANG['title']."</label>: <input type=\"text\" name=\"title\" id=\"title\" class=\"required\" title=\"".$LANG['title_news_title']."\"";
		if ($type == 'edit') {
			echo " value=\"$title\"";
		}
		echo " size=\"50\"/> &nbsp;<a href=\"#\" onclick=\"window.open('inc/upimages.php','name','width=700,height=500,scrollbars=yes,resizable=no,location=no,menubar=no,status=no'); return false;\">(".$LANG['upload_image'].")</a></div>\n<br/>";
		echo "\t\t\t\t<script type=\"text/javascript\">\n\t\t\t\t\tvar ftitle = new LiveValidation('title', { validMessage: \"\", wait: 500});\n\t\t\t\t\tftitle.add(Validate.Presence, {failureMessage: \"\"});\n\t\t\t\t</script>\n\t\t\t\t";
		echo "\t\t\t\t<script type=\"text/javascript\">var bb = new BBCode();</script>\n";
		displayMBToolbar();
		echo "\t\t\t\t<div><textarea name=\"post\" id=\"post\" rows=\"10\" cols=\"63\" class=\"required\"";
		if ($type == 'add') {
			echo "></textarea></div>\n\t\t\t\t";
		} else {
			echo ">$news</textarea></div>\n\t\t\t\t";
		}
		echo "<script type=\"text/javascript\">bb.init('post');</script>\n\t\t\t\t<p id=\"smileys\">";
		displaySmileys();
		echo "</p>\n\t\t\t\t<div>";
		if ($type == 'add') {
			echo "<input type=\"submit\" name=\"submitadd\" value=\"".$LANG['submit']."\"/>\n\t\t\t";
		} else {
			echo "<input type=\"hidden\" name=\"id\" value=\"$newsid\"/><input type=\"hidden\" name=\"user\" value=\"$user\"/><input type=\"submit\" name=\"submitedit\" value=\"".$LANG['edit']."\"/>\n\t\t\t";
		}
		echo "\t\t\t</div></form>\n\t\t\t<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>";
	}

	function displayLast5News () {
		global $LANG;
		$this->db->query("SELECT * FROM `fcms_news` ORDER BY `date` DESC LIMIT 5") or die('<h1>Last 5 News Error (familynews.class.php 126)</h1>' . mysql_error());
		if ($this->db->count_rows() > 0) {
			while ($row = $this->db->get_row()) {
				$monthName = fixDST(gmdate('F j, Y g:i a', strtotime($row['date'] . $this->tz_offset)), $this->cur_user_id, 'F');
				$date = fixDST(gmdate('F j, Y g:i a', strtotime($row['date'] . $this->tz_offset)), $this->cur_user_id, 'j, Y g:i a');
				$displayname = getUserDisplayName($row['user']);
				echo "<h2><a href=\"?getnews=".$row['user']."&amp;newsid=".$row['id']."\">".$row['title']."</a></h2><b>" . getLangMonthName($monthName) . " $date</b><span> - $displayname</span><p>";
				parse(rtrim(substr($row['news'], 0, 300)), 1);
				if (strlen($row['news']) > 300) {
					echo "...<br/><br/><a href=\"?getnews=".$row['user']."&amp;newsid=" . $row['id'] . "\">".$LANG['read_more']."</a>";
				}
				echo "</p><p>&nbsp;</p><p style=\"text-align: right\"><a href=\"#\" onclick=\"window.open('inc/familynews_comments.php?newsid=" . $row['id'] . "', '_Comments', 'height=400,width=550,resizable=yes,scrollbars=yes');return false;\">".$LANG['comments']."</a> - " . getNewsComments($row['id']) . "</p>\n\t\t\t";
			}
		} else {
			echo "<div class=\"info-alert\"><h2>".$LANG['info_news1']."</h2><p><i>".$LANG['info_news2']."</i></p><p><b>".$LANG['info_news3']."</b><br/>".$LANG['info_news4']." <a href=\"?addnews=yes\">".$LANG['add_news']."</a> ".$LANG['info_news5']."</p></div><p>&nbsp;</p><p>&nbsp;</p>\n";
		}
	}

	function hasNews ($userid) {
		$this->db->query("SELECT * FROM `fcms_news` WHERE `user` = $userid LIMIT 1") or die('<h1>Has News Error (familynews.class.php 145)</h1>' . mysql_error());
		if ($this->db->count_rows() > 0) {
			return true;
		} else {
			return false;
		}
	}

	function displayWhatsNewFamilyNews () {
		global $LANG;
		$today = date('Y-m-d');
		$tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
		$this->db->query("SELECT n.`id`, n.`title`, u.`id` AS userid, n.`date` FROM `fcms_users` AS u, `fcms_news` AS n WHERE u.`id` = n.`user` AND `date` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND `username` != 'SITENEWS' AND `password` != 'SITENEWS' ORDER BY `date` DESC LIMIT 0, 5");
		if ($this->db->count_rows() > 0) {
			echo "\t\t\t\t<h3>".$LANG['link_familynews']."</h3>\n\t\t\t\t<ul>\n";
			while ($row = $this->db->get_row()) {
				$displayname = getUserDisplayName($row['userid']);
				$monthName = gmdate('M', strtotime($row['date'] . $this->tz_offset));
				$date = gmdate('. j, Y, g:i a', strtotime($row['date'] . $this->tz_offset));
                if (
                    strtotime($row['date']) >= strtotime($today) && 
                    strtotime($row['date']) > $tomorrow
                ) {
                    $full_date = $LANG['today'];
                    $d = ' class="today"';
                } else {
                    $full_date = getLangMonthName($monthName) . $date;
                    $d = '';
                }
                echo "\t\t\t\t\t<li><div$d>$full_date</div>";
				echo "<a href=\"familynews.php?getnews=" . $row['userid'] . "&amp;newsid=";
                echo $row['id'] . "\">" . $row['title'] . "</a> - <a class=\"u\" ";
                echo "href=\"profile.php?member=" . $row['userid'] . "\">$displayname</a></li>\n";
			}
			echo "\t\t\t\t</ul>\n";
		}
	}

} ?>
