<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

class Poll {

	var $db;

	function Poll ($type, $host, $database, $user, $pass) {
		$this->db = new database($type, $host, $database, $user, $pass);
	}

	function placeVote ($userid, $optionid) {
		global $LANG;
		$this->db->query("SELECT user FROM fcms_poll_users WHERE user = $userid") or die('<h1>User Error (poll.class.php 14)</h1>' . mysql_error());
		if ($this->db->count_rows() > 0) {
			echo "<p class=\"center\">".$LANG['already_voted']."</p>\n\t\t";
		} else {
			$this->db->query("UPDATE fcms_poll_options SET votes = votes+1 WHERE id = $optionid") or die('<h1>+Vote Error (poll.class.php 18)</h1>' . mysql_error());
			$this->db->query("INSERT INTO `fcms_poll_users`(`user`, `option`) VALUES ($userid, $optionid)") or die('<h1>User Error (poll.class.php 19)</h1>' . mysql_error());
		}
	}

	function displayResults ($pollid) {
		global $LANG;
		$i = 0;
		$this->db->query("SELECT result.total, question, fcms_poll_options.id, `option` , votes FROM fcms_polls, fcms_poll_options, (SELECT sum( votes ) AS total, fcms_polls.id FROM fcms_polls, fcms_poll_options WHERE fcms_poll_options.poll_id = fcms_polls.id GROUP BY id) AS result WHERE fcms_polls.id = fcms_poll_options.poll_id AND fcms_polls.id = $pollid AND result.id = fcms_polls.id") or die('<h1>Result Error (poll.class.php 25)</h1>' . mysql_error());
		while ($row = $this->db->get_row()) {
			$votes = $row['votes'];
			$total = $row['total'];
			if($total < 1) { $percent = 0; } else { $percent = $votes/$total; }
			$width = round((140 * $percent) + 10, 0);
			if($total < 1) { $percent = 0; } else { $percent = round((($votes/$total) * 100), 0); }
			if ($i < 1) { echo "<h3>" . $row['question'] . "</h3>\n\t\t<ul class=\"poll\">\n\t\t"; }
			echo "\t<li>\n\t\t\t\t<a href=\"#\">" .  $row['option'] . "</a>\n\t\t\t\t";
			echo "<span class=\"count\">$votes</span>\n\t\t\t\t<span class=\"index\" style=\"width:$percent%\">$percent%</span>\n\t\t\t</li>\n\t\t";
			$i++;
		}
		echo "</ul>\n\t\t<p class=\"poll\">".$LANG['total_votes'].": $total</p><p><a href=\"?action=pastpolls\">".$LANG['past_polls']."</a></p>\n\t\t";
	}

	function displayPoll ($pollid = '0') {
		global $LANG;
		$poll_exists = true;
		if ($pollid !== '0') {
			$this->db->query("SELECT fcms_polls.id, question, fcms_poll_options.id AS option_id, `option` FROM fcms_polls, fcms_poll_options WHERE fcms_polls.id = $pollid AND fcms_polls.id = fcms_poll_options.poll_id") or die('<h1>Poll Error (poll.class.php 45)</h1>' . mysql_error());
			if ($this->db->count_rows() <= 0) { $poll_exists = false; }
		} else {
			$this->db->query("SELECT MAX(`id`) AS max FROM fcms_polls") or die('<h1>Max Error (poll.class.php 47)</h1>' . mysql_error());
			$r = $this->db->get_row();
			$max = $r['max'];
			if (!is_null($r['max'])) { 
				$this->db->query("SELECT fcms_polls.id, question, fcms_poll_options.id AS option_id, `option` FROM fcms_polls, fcms_poll_options WHERE fcms_polls.id = $max AND fcms_polls.id = fcms_poll_options.poll_id") or die('<h1>Poll Error (poll.class.php 50)</h1>' . mysql_error());
			} else {
				$poll_exists = false;
			}
		}
		if ($poll_exists) {
			echo "<h2 class=\"pollmenu\">".$LANG['link_admin_polls']."</h2>";
			echo "<form method=\"post\" action=\"home.php\">";
			$i = 0;
			$id = '';
			while ($row = $this->db->get_row()) {
				$id = $row['id'];
				if ($i < 1) { echo "<h3>" . $row['question'] . "</h3>"; }
				echo "<p><input type=\"radio\" name=\"option_id\" value=\"" . $row['option_id'] . "\"/><span>" . $row['option'] . "</span></p>";
				$i++;
			}
			echo "<p><a href=\"?action=results&amp;poll_id=$id\">".$LANG['view_results']."</a> | <a href=\"?action=pastpolls\">".$LANG['past_polls']."</a></p><p><input type=\"hidden\" name=\"poll_id\" value=\"$id\"/><input type=\"submit\" value=\"".$LANG['vote']."\" name=\"vote\"/></p></form>\n\t\t";
		}	
	}

	function displayPastPolls () {
		global $LANG;
		echo "<br/><h2>".$LANG['link_admin_polls']."</h2>";
		echo "<h3>".$LANG['prev_polls']."</h3>";
		$this->db->query("SELECT * FROM fcms_polls ORDER BY started DESC") or die('<h1>Polls Error (poll.class.php 67)</h1>' . mysql_error());
		while ($row = $this->db->get_row()) {
			echo "<p><a href=\"?poll_id=" . $row['id'] . "\">" . $row['question'] . "</a></p>";
		}
		echo "\n\t\t";
	}

} ?>