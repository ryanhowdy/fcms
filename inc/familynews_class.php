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

    function displayNewsList ()
    {
        global $LANG;
        $sql = "SELECT u.`id`, `fname`, `lname`, `displayname`, `username`, MAX(`date`) AS d 
                FROM `fcms_news` AS n, `fcms_users` AS u, `fcms_user_settings` AS s 
                WHERE u.`id` = n.`user` 
                AND u.`id` = s.`user` GROUP BY id ORDER BY d DESC";
        $this->db->query($sql) or displaySQLError(
            'News List Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if ($this->db->count_rows() > 0) {
            echo <<<HTML
            <div id="news-list">
                <h2>{$LANG['link_familynews']}</h2>
                <ul>

HTML;
            while($r = $this->db->get_row()) {
                $monthName = fixDST(gmdate('F j, Y g:i a', strtotime($r['d'] . $this->tz_offset)), $this->cur_user_id, 'M');
                $monthName = getLangMonthName($monthName);
                $date = fixDST(gmdate('F j, Y g:i a', strtotime($r['d'] . $this->tz_offset)), $this->cur_user_id, '. j');
                $displayname = getUserDisplayName($r['id']);
                echo "<li><a href=\"familynews.php?getnews=".$r['id']."\">$displayname  <small>[$monthName $date]</small></a></li>\n";
            }
            echo <<<HTML
                </ul>
            </div>

HTML;
        }
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
			echo '
            <div class="news-post">
                <h2>
                    <a href="?getnews='.$usersnews.'&amp;newsid='.$row['id'].'">'.$row['title'].'</a>
                </h2>
                <span class="date">
                    '.$monthName.' '.$date.'</b> - '.$displayname;
			if ($_SESSION['login_id'] == $usersnews || checkAccess($_SESSION['login_id']) < 2) {
				echo ' &nbsp;
                    <form method="post" action="familynews.php">
                        <div>
                            <input type="hidden" name="user" value="'.$usersnews.'"/>
                            <input type="hidden" name="id" value="'.$row['id'].'"/>
                            <input type="hidden" name="title" value="'.htmlentities($row['title'], ENT_COMPAT, 'UTF-8').'"/>
                            <input type="hidden" name="news" value="'.htmlentities($row['news'], ENT_COMPAT, 'UTF-8').'"/>
                            <input type="submit" name="editnews" value="'.$LANG['edit'].'" class="editbtn" title="'.$LANG['title_edit_news'].'"/>
                        </div>
                    </form>';
			}
			if (checkAccess($_SESSION['login_id']) < 2) {
				echo ' &nbsp;
                    <form class="delnews" method="post" action="familynews.php?getnews='.$usersnews.'">
                        <div>
                            <input type="hidden" name="user" value="'.$usersnews.'"/>
                            <input type="hidden" name="id" value="'.$row['id'].'"/>
                            <input type="submit" name="delnews" value="'.$LANG['delete'].'" class="delbtn" title="'.$LANG['title_delete_news'].'"/>
                        </div>
                    </form>';
			}
            echo '
                </span>
                <p>';
			if ($id <= 0) {
				parse(rtrim(substr($row['news'], 0, 300)));
				if (strlen($row['news']) > 300) {
                    echo '...<br/><br/>
                <a href="?getnews='.$usersnews.'&amp;newsid='.$row['id'].'">'.$LANG['read_more'].'</a>';
                }
				echo '
                </p>
                <p>&nbsp;</p>
                <p class="news-comments">
                    <a href="#" onclick="window.open(\'inc/familynews_comments.php?newsid='.$row['id'].'\', \'_Comments\', \'height=400,width=550,resizable=yes,scrollbars=yes\');return false;">'.$LANG['comments'].'</a> - '.getNewsComments($row['id']).'
                </p>
            </div>';
			} else {
				parse($row['news']);
				echo '
            </div>
            <p>&nbsp;</p>
            <h3>'.$LANG['comments'].'</h3>
            <p class="center">
                <form action="?getnews='.$usersnews.'&amp;newsid='.$id.'" method="post">
                    '.$LANG['add_comment'].'<br/>
                    <input type="text" name="comment" id="comment" size="50" title="'.$LANG['add_comment'].'"/> 
                    <input type="submit" name="addcom" id="addcom" value="'.$LANG['add_comment'].'" class="gal_addcombtn"/>
                </form>
            </p>
            <p class="center">&nbsp;</p>';
                $sql = "SELECT c.id, comment, `date`, fname, lname, username, user, avatar  
                        FROM fcms_news_comments AS c, fcms_users AS u 
                        WHERE news = $id 
                        AND c.user = u.id 
                        ORDER BY `date`";
				$this->db2->query($sql) or displaySQLError(
                    'News Comments Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
                );
				if ($this->db->count_rows() > 0) { 
					while($row = $this->db2->get_row()) {
						$displayname = getUserDisplayName($row['user']);
						if ($this->cur_user_id == $row['user'] || checkAccess($this->cur_user_id) < 2) {
							echo '
            <div class="comment_block clearfix">
                <form class="delcom" action="?getnews='.$usersnews.'&amp;newsid='.$id.'" method="post">
                    <input type="submit" name="delcom" id="delcom" value="'.$LANG['delete'].'" class="gal_delcombtn" title="'.$LANG['title_del_comment'].'"/>
                    <img src="gallery/avatar/'.$row['avatar'].'">
                    <b>'.$displayname.'</b>
                    <span>'.$date.'</span>
                    <p>';
                            echo htmlentities(stripslashes($row['comment']), ENT_COMPAT, 'UTF-8');
                            echo '</p>
                    <input type="hidden" name="id" value="'.$row['id'].'">
                </form>
            </div>';
						} else {
							echo '
            <div class="comment_block clearfix">
                    <img src="avatar/'.$row['avatar'].'">
                    <b>'.$displayname.'</b>
                    <span>'.$date.'</span>
                    <p>';
                            echo htmlentities(stripslashes($row['comment']), ENT_COMPAT, 'UTF-8');
                            echo '</p>
                </form>
            </div>';
						}
					}
				} else {
					echo '
            <p class="center">'.$LANG['no_comments'].'</p>';
				}
			}
		}
		if ($id <= 0) {
            // TODO
            // Move this into global function
            $sql = "SELECT count(id) AS c FROM fcms_news WHERE user = $usersnews";
			$this->db2->query($sql) or displaySQLError(
                'Count Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
			while ($r=$this->db2->get_row()) {
                $newscount = $r['c'];
            }
			$total_pages = ceil($newscount / 5); 
			if ($total_pages > 1) {
				echo '
            <div class="pages clearfix">
                <ul>'; 
				if ($page > 1) { 
					$prev = ($page - 1); 
					echo '
                    <li><a title="'.$LANG['title_first_page'].'" class="first" href="familynews.php?getnews='.$usersnews.'&amp;newspage=1"></a></li>
                    <li><a title="'.$LANG['title_prev_page'].'" class="previous" href="familynews.php?getnews='.$usersnews.'&amp;newspage='.$prev.'"></a></li>';
				} 
				if ($total_pages > 8) {
					if ($page > 2) {
						for ($i = ($page-2); $i <= ($page+5); $i++) {
							if ($i <= $total_pages) {
                                echo '
                    <li><a href="familynews.php?getnews='.$usersnews.'&amp;newspage='.$i.'"';
                                if ($page == $i) {
                                    echo ' class="current"';
                                }
                                echo '>'.$i.'</a></li>';
                            }
						} 
					} else {
						for ($i = 1; $i <= 8; $i++) {
                            echo '
                    <li><a href="familynews.php?getnews='.$usersnews.'&amp;newspage='.$i.'"';
                            if ($page == $i) {
                                echo ' class="current"';
                            }
                            echo '>'.$i.'</a></li>';
                        } 
					}
				} else {
					for ($i = 1; $i <= $total_pages; $i++) {
						echo '
                    <li><a href="familynews.php?getnews='.$usersnews.'&amp;newspage='.$i.'"';
                        if ($page == $i) {
                            echo ' class="current"';
                        }
                        echo '>'.$i.'</a></li>';
					} 
				}
				if ($page < $total_pages) { 
					$next = ($page + 1); 
					echo '
                    <li><a title="'.$LANG['title_next_page'].'" class="next" href="familynews.php?getnews='.$usersnews.'&amp;newspage='.$next.'"></a></li>
                    <li><a title="'.$LANG['title_last_page'].'" class="last" href="familynews.php?getnews='.$usersnews.'&amp;newspage='.$total_pages.'"></a></li>';
				} 
				echo '
                </ul>
            </div>';
			}
		}
		echo "<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>\n";
	}

	function displayForm ($type, $user = '0', $newsid = '0', $title='error', $news = 'error')
    {
		global $LANG;
		echo '
            <script type="text/javascript" src="inc/livevalidation.js"></script>
            <script type="text/javascript" src="inc/messageboard.inc.js"></script>';
		if ($type == 'edit') {
			echo '
            <form method="post" id="editform" action="familynews.php">
                <h3>'.$LANG['edit_news'].'</h3>';			
		} else {
			echo '
            <form method="post" id="addform" action="familynews.php">
                <h3>'.$LANG['add_news'].'</h3>';
		}
		echo '
                <div>
                    <label for="title">'.$LANG['title'].'</label>: 
                    <input type="text" name="title" id="title" title="'.$LANG['title_news_title'].'"';
		if ($type == 'edit') {
			echo ' value="'.$title.'"';
		}
		echo ' size="50"/> &nbsp;
                    <a id="upimages" class="hideme" href="#" onclick="window.open(\'inc/upimages.php\',\'name\',\'width=700,height=500,scrollbars=yes,resizable=no,location=no,menubar=no,status=no\'); return false;">('.$LANG['upload_image'].')</a>
                </div><br/>
                <script type="text/javascript">
                    var ftitle = new LiveValidation(\'title\', { onlyOnSubmit:true });
                    ftitle.add(Validate.Presence, { failureMessage: "" });
                </script>
                <script type="text/javascript">var bb = new BBCode();</script>';
		displayMBToolbar();
        echo '
                <div><textarea name="post" id="post" rows="10" cols="63"';
		if ($type == 'add') {
			echo "></textarea></div>";
		} else {
			echo ">$news</textarea></div>";
		}
		echo '
                <script type="text/javascript">bb.init(\'post\');</script>
                <p id="smileys" class="hideme">';
		displaySmileys();
		echo '</p>
                <div>';
		if ($type == 'add') {
			echo '
                    <input type="submit" name="submitadd" value="'.$LANG['submit'].'"/>';
		} else {
			echo '
                    <input type="hidden" name="id" value="'.$newsid.'"/>
                    <input type="hidden" name="user" value="'.$user.'"/>
                    <input type="submit" name="submitedit" value="'.$LANG['edit'].'"/>';
		}
		echo '
                </div>
            </form>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>';
	}

    function displayLast5News () {
        global $LANG;
        $this->db->query("SELECT * FROM `fcms_news` ORDER BY `date` DESC LIMIT 5") or die('<h1>Last 5 News Error (familynews.class.php 126)</h1>' . mysql_error());
        if ($this->db->count_rows() > 0) {
            while ($row = $this->db->get_row()) {
                $monthName = fixDST(gmdate('F j, Y g:i a', strtotime($row['date'] . $this->tz_offset)), $this->cur_user_id, 'F');
                $date = fixDST(gmdate('F j, Y g:i a', strtotime($row['date'] . $this->tz_offset)), $this->cur_user_id, 'j, Y g:i a');
                $displayname = getUserDisplayName($row['user']);
                echo '
                    <div class="news-post">
                        <h2>
                            <a href="?getnews='.$row['user'].'&amp;newsid='.$row['id'].'">'.$row['title'].'</a>
                        </h2>
                        <span class="date">'.getLangMonthName($monthName).' '.$date.' - '.$displayname.'</span>
                        <p>';
                parse(rtrim(substr($row['news'], 0, 300)));
                if (strlen($row['news']) > 300) {
                    echo '...<br/><br/>
                            <a href="?getnews='.$row['user'].'&amp;newsid='.$row['id'].'">'.$LANG['read_more'].'</a>';
                }
                echo '
                        </p>
                        <p>&nbsp;</p>
                        <p class="news-comments">
                            <a href="#" onclick="window.open(\'inc/familynews_comments.php?newsid='.$row['id'].'\', 
                                \'_Comments\', \'height=400,width=550,resizable=yes,scrollbars=yes\'); return false;">
                                '.$LANG['comments'].'
                            </a> - '.getNewsComments($row['id']).'
                        </p>
                    </div>';
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
