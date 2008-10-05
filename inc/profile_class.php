<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

class Profile {

	var $db;
	var $db2;
	var $tz_offset;
	var $cur_user_id;

	function Profile ($current_user_id, $type, $host, $database, $user, $pass) {
		$this->cur_user_id = $current_user_id;
		$this->db = new database($type, $host, $database, $user, $pass);
		$this->db2 = new database($type, $host, $database, $user, $pass);
		$this->db->query("SELECT timezone FROM fcms_users WHERE id = $current_user_id") or die('<h1>Timezone Error (profile.class.php 16)</h1>' . mysql_error());
		$row = $this->db->get_row();
		$this->tz_offset = $row['timezone'];
	}

	function displayProfile ($userid) {
		global $LANG;
		$this->db->query("SELECT u.fname, u.lname, u.email, u.birthday, u.avatar, u.username, u.joindate, u.activity FROM fcms_users AS u, fcms_address AS a WHERE u.id = $userid AND u.id = a.user") or die('<h1>Profile Error (profile.class.php 30)</h1>' . mysql_error());
		$row = $this->db->get_row();
		$points = round(getUserRankById($userid), 2);
		$pts = 0;
		if($points > 50) { $rank_img = "<div title=\"".$LANG['elder']." ($points)\" class=\"rank7\"></div>"; $rank = $LANG['elder']; $next_rank = "<i>none</i>"; }
		elseif($points > 30) { $rank_img = "<div title=\"".$LANG['adult']." ($points)\" class=\"rank6\"></div>"; $rank = $LANG['adult']; $next_rank = $LANG['elder']; $pts = 50; }
		elseif($points > 20) { $rank_img = "<div title=\"".$LANG['mature_adult']." ($points)\" class=\"rank5\"></div>"; $rank = $LANG['mature_adult']; $next_rank = $LANG['adult']; $pts = 30; }
		elseif($points > 10) { $rank_img = "<div title=\"".$LANG['young_adult']." ($points)\" class=\"rank4\"></div>"; $rank = $LANG['young_adult']; $next_rank = $LANG['mature_adult']; $pts = 20; }
		elseif($points > 5) { $rank_img = "<div title=\"".$LANG['teenager']." ($points)\" class=\"rank3\"></div>"; $rank = $LANG['teenager']; $next_rank = $LANG['young_adult']; $pts = 10; }
		elseif($points > 3) { $rank_img = "<div title=\"".$LANG['kid']." ($points)\" class=\"rank2\"></div>"; $rank = $LANG['kid']; $next_rank = $LANG['teenager']; $pts = 5; }
		elseif($points > 1) { $rank_img = "<div title=\"".$LANG['toddler']." ($points)\" class=\"rank1\"></div>"; $rank = $LANG['toddler']; $next_rank = $LANG['kid']; $pts = 3; }
		else { $rank_img = "<div title=\"".$LANG['baby']." ($points)\" class=\"rank0\"></div>"; $rank = $LANG['baby']; $next_rank = $LANG['toddler']; $pts = 1; }
		echo "<div id=\"side-info\"><div><b>".$LANG['mem_details']."</b></div><div class=\"center\"><img src=\"gallery/avatar/" . $row['avatar'] . "\" alt=\"avatar\"/><br/>" . $row['username'] . "</div>";
		$monthName = gmdate("F", strtotime($row['joindate']));
		$date = gmdate(" j, Y", strtotime($row['joindate']));
		echo "<div class=\"small\"><p><b>".$LANG['join_date'].":</b><br/>".$LANG[$monthName]."$date</p>";
		$monthName = gmdate("F", strtotime($row['activity'] . $this->tz_offset));
		$date = fixDST(gmdate('n/j/Y g:i a', strtotime($row['activity'] . $this->tz_offset)), $_SESSION['login_id'], 'j, Y, g:i a');
		echo "<p><b>".$LANG['last_visit'].":</b><br/>".$LANG[$monthName]." $date</div>";
		if (checkAccess($_SESSION['login_id']) != 10) {
			echo "<div><b>".$LANG['stats']."</b></div><div class=\"small\"><p><b>".$LANG['posts'].":</b> " . getPostsById($userid) . "</p><p><b>".$LANG['photos'].":</b> " . getPhotosById($userid) . "</p>";
			if (usingFamilyNews()) {
				echo "<p><b>".$LANG['link_news']."</b> " . getFamilyNewsById($userid) . "</p>";
			}
			echo "<p><b>".$LANG['comments'].":</b> " . getCommentsById($userid) . "</p></div>";
		}
		echo "</div>\n<div class=\"main-info\"><h3>" . $row['lname'] . ", " . $row['fname'] . "</h3>$rank_img</div>\n";
		echo "<div class=\"main-info\"><h2>".$LANG['rank']."</h2><div><b>".$LANG['points'].":</b> $points</div><div><b>".$LANG['rank'].":</b> $rank</div>";
		$ptsToGo = $pts - round($points, 2);
		echo "<div><b>".$LANG['next_rank'].":</b> $next_rank";
		if ($ptsToGo > 0) {
			echo " <small>($ptsToGo ".$LANG['pts_go'].")</small></div>";
			$this->displayPointsToGo($ptsToGo);
		} else { 
			echo "</div>";
		}
		echo "</div>\n";
		if (checkAccess($_SESSION['login_id']) < 8 && checkAccess($_SESSION['login_id']) != 5) {
			echo "<div class=\"main-info\"><h2>".$LANG['last5_posts']."</h2>"; $this->displayLast5Posts($userid); echo"</div>\n";
		}
		if (checkAccess($_SESSION['login_id']) <= 3 || checkAccess($_SESSION['login_id']) == 8) {
			echo "<div class=\"main-info\"><h2>".$LANG['last5_photos']."</h2>"; $this->displayLast5Photos($userid); echo "</div>\n";
		}
	}

	function displayAll () {
		global $LANG;
		echo "<script src=\"inc/prototype.js\" type=\"text/javascript\"></script>\n<script type=\"text/javascript\" src=\"inc/tablesort.js\"></script>";
		echo "<table class=\"sortable\">\n<thead><tr><th>".$LANG['username']."</th><th>".$LANG['fname']."</th><th>".$LANG['lname']."</th><th>".$LANG['posts']."</th><th>".$LANG['photos']."</th><th class=\"sortfirstdesc\">".$LANG['rank']."</th><th>".$LANG['age']."</th></tr></thead>\n<tbody>";
		$this->db->query("SELECT u.id, u.fname, u.lname, u.birthday, u.avatar, u.username, u.activity, a.state FROM fcms_users AS u, fcms_address AS a WHERE u.id = a.user AND u.password != 'NONMEMBER'") or die('<h1>Users Error (profile.class.php 53)</h1>' . mysql_error());
		while($row = $this->db->get_row()) {
			$birthday = $row['birthday'];
			list($year,$month,$day) = explode("-",$birthday);
			$year_diff  = date("Y") - $year;
			$month_diff = date("m") - $month;
			$day_diff   = date("d") - $day;
			if ($month_diff < 0) { $year_diff--; } elseif (($month_diff==0) && ($day_diff < 0)) { $year_diff--; }
			$birthday = $year_diff;
			$points = getUserRankById($row['id']);
			$points = round($points, 2);
			if($points > 50) { $uname_display = "<div title=\"".$LANG['elder']." ($points)\" class=\"rank7\"></div>"; }
			elseif($points > 30) { $uname_display = "<div title=\"".$LANG['adult']." ($points)\" class=\"rank6\"></div>"; }
			elseif($points > 20) { $uname_display = "<div title=\"".$LANG['mature_adult']." ($points)\" class=\"rank5\"></div>"; }
			elseif($points > 10) { $uname_display = "<div title=\"".$LANG['young_adult']." ($points)\" class=\"rank4\"></div>"; }
			elseif($points > 5) { $uname_display = "<div title=\"".$LANG['teenager']." ($points)\" class=\"rank3\"></div>"; }
			elseif($points > 3) { $uname_display = "<div title=\"".$LANG['kid']." ($points)\" class=\"rank2\"></div>"; }
			elseif($points > 1) { $uname_display = "<div title=\"".$LANG['toddler']." ($points)\" class=\"rank1\"></div>"; }
			else { $uname_display = "<div title=\"".$LANG['baby']." ($points)\" class=\"rank0\"></div>"; }
			echo "<tr><td><a href=\"profile.php?member=" . $row['id'] . "\">" . $row['username'] . "</a><br/>$uname_display</td>";
			echo "<td>" . $row['fname'] . "</td><td>" . $row['lname'] . "</td><td>" . getPostsById($row['id']) . "</td><td>" . getPhotosById($row['id']) . "</td>";
			echo "<td>$points</td><td>$birthday</td></tr>\n";
		}
		echo "</tbody>\n</table>";
	}

	function displayPointsToGo ($pts) {
		global $LANG;
		$posts = ceil($pts / (1 / 75));
		$photos = ceil($pts / (1 / 25));
		$comments = ceil($pts / (1 / 20));
		$calendar = ceil($pts / (1 / 5));
		$news = ceil($pts / (1 / 10));
		echo "<div><small><i>&nbsp;&nbsp;&nbsp; $posts ".$LANG['new_posts']." &nbsp;&nbsp;- ".$LANG['or']." -</i></small></div>";
		echo "<div><small><i>&nbsp;&nbsp;&nbsp; $photos ".$LANG['new_photos']." &nbsp;&nbsp;- ".$LANG['or']." -</i></small></div>";
		echo "<div><small><i>&nbsp;&nbsp;&nbsp; $comments ".$LANG['new_comments']." &nbsp;&nbsp;- ".$LANG['or']." -</i></small></div>";
		echo "<div><small><i>&nbsp;&nbsp;&nbsp; $calendar ".$LANG['new_cal_entries']." &nbsp;&nbsp;- ".$LANG['or']." -</i></small></div>";
		echo "<div><small><i>&nbsp;&nbsp;&nbsp; $comments ".$LANG['new_news_entries']." &nbsp;&nbsp;- ".$LANG['or']." -</i></small></div>";
	}

	function displayLast5Posts ($userid) {
		global $LANG;
		$this->db2->query("SELECT t.id, subject, `date`, post FROM fcms_board_posts AS p, fcms_board_threads AS t, fcms_users AS u WHERE t.id = p.thread AND p.user = u.id AND u.id = $userid ORDER BY `date` DESC LIMIT 0, 5") or die('<h1>Posts Error (profile.class.php 80)</h1>' . mysql_error());
		if ($this->db2->count_rows() > 0) {
			while($row = $this->db2->get_row()) {
				$monthName = gmdate('F', strtotime($row['date'] . $this->tz_offset));
				$date = gmdate(' j, Y, g:i a', strtotime($row['date'] . $this->tz_offset));
				$search = array('/\[ins\](.*?)\[\/ins\]/is', '/\[del\](.*?)\[\/del\]/is', '/\[h1\](.*?)\[\/h1\]/is', '/\[h2\](.*?)\[\/h2\]/is', '/\[h3\](.*?)\[\/h3\]/is', '/\[h4\](.*?)\[\/h4\]/is', '/\[h5\](.*?)\[\/h5\]/is', '/\[h6\](.*?)\[\/h6\]/is', 
					'/\[b\](.*?)\[\/b\]/is', '/\[i\](.*?)\[\/i\]/is', '/\[u\](.*?)\[\/u\]/is', '/\[url\=(.*?)\](.*?)\[\/url\]/is', '/\[url\](.*?)\[\/url\]/is', '/\[align\=(left|center|right)\](.*?)\[\/align\]/is','/\[img\=(.*?)\]/is', '/\[img\](.*?)\[\/img\]/is', 
					'/\[mail\=(.*?)\](.*?)\[\/mail\]/is', '/\[mail\](.*?)\[\/mail\]/is', '/\[font\=(.*?)\](.*?)\[\/font\]/is', '/\[size\=(.*?)\](.*?)\[\/size\]/is', '/\[color\=(.*?)\](.*?)\[\/color\]/is', '/\[span\](.*?)\[\/span\]/is', '/\[span\=(.*?)\](.*?)\[\/span\]/is');
				$replace = array('$1', '$1', '$1', '$1', '$1', '$1', '$1', '$1', '$1', '$1', '$1', '$2', '$1', '$2', '$1', '1', '$2', '$1', '$2', '$2','$2', '$1', '$2');
				$post = preg_replace($search, $replace, stripslashes($row['post']));
				$subject = stripslashes($row['subject']);
				$pos = strpos($subject, '#ANOUNCE#');
				if($pos !== false) { $subject = substr($subject, 9, strlen($subject)-9); }
				echo "<p class=\"small\"><a href=\"messageboard.php?thread=" . $row['id'] . "\">$subject</a> <i>".$LANG[$monthName]."$date</i><br/>$post</p>";
			}
		} else {
			echo "<p>".$LANG['none']."</p>";
		}
	}

	function displayLast5Photos ($userid) {
		global $LANG;
		$this->db2->query("SELECT * FROM `fcms_gallery_photos` WHERE user = $userid ORDER BY `date` DESC LIMIT 5") or die('<h1>Photos Error (profile.class.php 93)</h1>' . mysql_error());
		if ($this->db2->count_rows() > 0) {
			echo "<div class=\"gal_row\">";
			while ($row = $this->db2->get_row()) {
				echo "<div class=\"cat\"><a href=\"gallery/index.php?uid=$userid&amp;cid=" . $row['category'] . "&amp;pid=" . $row['id'] . "\"><img class=\"photo\" src=\"gallery/photos/member" . $row['user'] . "/tb_" . $row['filename'] . "\" alt=\"\"/></a></div>";
			}
			echo "</div>";
		} else {
			echo "<p>".$LANG['none']."</p>";
		}
	}

	function displayAwards () {
		global $LANG;
		$this->db->query("SELECT `count` FROM `fcms_user_awards` WHERE `id` = 1") or die('<h1>Awards Error (profile.class.php 130)</h1>' . mysql_error());
		$check = $this->db->get_row();
		if ($check['count'] > 0) {
			$this->db->query("SELECT * FROM `fcms_user_awards` WHERE `type` = 'top5poster'") or die('<h1>Awards Error (profile.class.php 133)</h1>' . mysql_error());
			echo "<h2>".$LANG['link_board']."</h2><h3><dfn title=\"".$LANG['title_most_talkative']."\"></dfn>".$LANG['most_talkative']."</h3><ol>";
			while ($row = $this->db->get_row()) {
				if ($row['count'] > 0) { echo "<li><a class=\"u\" href=\"profile.php?member=" . $row['user'] . "\">" . getUserDisplayName($row['user']) . "</a> - " . $row['count'] . " ".$LANG['posts_lc']."</li>"; }
			}
			echo "</ol>";
			$this->db->query("SELECT * FROM `fcms_user_awards` WHERE `type` = 'topthreadstarter'") or die('<h1>Awards Error (profile.class.php 139)</h1>' . mysql_error());
			$row = $this->db->get_row();
			$this->db2->query("SELECT `subject` FROM `fcms_board_threads` WHERE `id` = " . $row['value']) or die('<h1>Subject Error (profile.class.php 141)</h1>' . mysql_error());
			$row2 = $this->db2->get_row();
			$pos = strpos($row2['subject'], '#ANOUNCE#');
			if ($pos !== false) { $subject = substr($row2['subject'], 9, strlen($row2['subject'])-9); } else { $subject = $row2['subject']; }
			echo "<p>&nbsp;</p><h3><dfn title=\"".$LANG['title_conf_starter']."\">".$LANG['conf_starter']."</dfn></h3>";
			echo "<a class=\"u\" href=\"profile.php?member=" . $row['user'] . "\">" . getUserDisplayName($row['user']) . "</a> - <a href=\"messageboard.php?thread=" . $row['value'] . "\">$subject</a>, " . $row['count'] . " ".$LANG['replies_lc'];
			$this->db->query("SELECT * FROM `fcms_user_awards` WHERE `type` = 'mostsmileys'") or die('<h1>Awards Error (profile.class.php 147)</h1>' . mysql_error());
			$row = $this->db->get_row();
			echo "<p>&nbsp;</p><h3><dfn title=\"".$LANG['title_smile']."\">".$LANG['smile']."</dfn></h3><a class=\"u\" href=\"profile.php?member=" . $row['user'] . "\">" . getUserDisplayName($row['user']) . "</a>";
		} else {
			echo "<p class=\"info-alert\">".$LANG['info_no_board_awards']."</p>";
		}
		$this->db->query("SELECT `count` FROM `fcms_user_awards` WHERE `id` = 8") or die('<h1>Awards Error (profile.class.php 153)</h1>' . mysql_error());
		$check = $this->db->get_row();
		if ($check['count'] > 0) {
			$this->db->query("SELECT * FROM `fcms_user_awards` WHERE `type` = 'top5photo'") or die('<h1>Awards Error (profile.class.php 156)</h1>' . mysql_error());
			echo "<h2>".$LANG['link_gallery']."</h2><h3><dfn title=\"".$LANG['title_photographer']."\">".$LANG['photographer']."</dfn></h3><ol>";
			while ($row = $this->db->get_row()) {
				if ($row['count'] > 0) { echo "<li><a class=\"u\" href=\"profile.php?member=" . $row['user'] . "\">" . getUserDisplayName($row['user']) . "</a> - " . $row['count'] . " ".$LANG['photos_lc']."</li>"; }
			}
			echo "</ol><p>&nbsp;</p><h3><dfn title=\"".$LANG['title_photogenic']."\">".$LANG['photogenic']."</dfn></h3>";
			$this->db->query("SELECT * FROM `fcms_user_awards` WHERE `type` = 'topviewedphoto'") or die('<h1>Awards Error (profile.class.php 162)</h1>' . mysql_error());
			$row = $this->db->get_row();
			$this->db2->query("SELECT `user`, `filename`, `category` FROM `fcms_gallery_photos` WHERE `id` = " . $row['value']) or die('<h1>Filename Error (profile.class.php 164)</h1>' . mysql_error());
			$row2 = $this->db2->get_row();
			echo "<a class=\"u\" href=\"profile.php?member=" . $row['user'] . "\">" . getUserDisplayName($row['user']) . "</a><br/><a href=\"gallery/index.php?uid=" . $row['user'] . "&cid=" . $row2['category'] . "&pid=" . $row['value'] . "\"><img src=\"gallery/photos/member" . $row2['user'] . "/tb_" . $row2['filename'] . "\"/></a><p>&nbsp;</p>";
		} else {
			echo "<p class=\"info-alert\">".$LANG['info_no_gallery_awards']."</p>";
		}
	}

} ?>