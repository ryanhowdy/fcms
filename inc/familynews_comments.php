<?php
session_start();
include_once('config_inc.php');
include_once('util_inc.php');
include_once('language.php');

// Check that the user is logged in
isLoggedIn();

header("Cache-control: private");
$timezone_sql = mysql_query("SELECT `timezone` FROM `fcms_user_settings` WHERE `id` = " . $_SESSION['login_id']) or die('<h1>Timezone Error (familynews_comments.class.php 24)</h1>' . mysql_error());
$ftimezone = mysql_fetch_array($timezone_sql);
$tz_offset = $ftimezone['timezone'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo getSiteName() . " - " . $LANG['poweredby'] . " " . getCurrentVersion(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script type="text/javascript" src="prototype.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, 'load', function() {
    if (!$$('div.comment_block input[type="submit"]')) { return; }
    $$('div.comment_block input[type="submit"]').each(function(item) {
        item.onclick = function() { return confirm('<?php echo $LANG['js_del_comment']; ?>'); };
        var hid = document.createElement('input');
        hid.setAttribute('type', 'hidden');
        hid.setAttribute('name', 'confirmed');
        hid.setAttribute('value', 'true');
        item.insert({'after':hid});
    });
    return true;
});
//]]>
</script>
<?php
// TODO
// Remove all this css and move into style.css or fcms-core.css
?>
<style type="text/css">
.right { text-align: right; }
.center { text-align: center; }
.edit_del_photo { margin: 0 auto -12px auto; width: 500px; text-align: right; }
.gal_delcombtn, .gal_addcombtn, .gal_editbtn, .gal_delbtn { border: 0; width: 16px; height: 16px; cursor: pointer; vertical-align: middle; }
.gal_delcombtn { background: url("../themes/default/images/comments_delete.gif") top left no-repeat; }
.gal_addcombtn { background: url("../themes/default/images/comments_add.gif") top left no-repeat; }
.gal_delbtn { background: url("../themes/default/images/image_delete.gif") top left no-repeat; }
.gal_editbtn { background: url("../themes/default/images/image_edit.gif") top left no-repeat; }
.comment_block { margin: 0 auto 15px auto; padding: 3px; width: 450px; border: 1px solid #c1c1c1; background-color: #f5f5f5; }
.comment_block form { margin: 0; }
.comment_block input, .comment_block span { float: right; padding-right: 5px; }
.comment_block img { display: block; float: left; margin-right: 5px; border: 2px solid #e6e6e6; }
.gal_delcombtn, .gal_addcombtn {
    border: 0; cursor: pointer; cursor: hand; font-size: 0px; height: 16px; line-height: 0px;
    * overflow: hiddent;
    * padding: 40px 0 0 0;
    text-indent: -9999px; width: 16px; }
.info-alert { text-align: left; padding: 5px 20px 5px 45px; margin: 50px 1%; background-color: #dbe9fd; border-top: 2px solid #74a8f5; border-bottom: 2px solid #74a8f5; }
.clearfix:after { content:"."; display:block; height:0; clear:both; visibility:hidden; }
.clearfix { display:inline-block; }
/* Hides from IE-mac \*/
* html .clearfix { height:1%; }
.clearfix { display:block; }
/* End hide from IE-mac */
</style>
</head>
<body>
<?php
if (isset($_GET['newsid'])) {

    $show = true;
	$news_id = $_GET['newsid'];

    // Add Comment
	if (isset($_POST['addcom'])) {
		$com = ltrim($_POST['comment']);
		if (!empty($com)) {
            $sql = "INSERT INTO `fcms_news_comments`
                        (`news`, `comment`, `date`, `user`) 
                    VALUES 
                        ($news_id, '" . addslashes($com) . "', NOW(), " . $_SESSION['login_id'] . ")";
            mysql_query($sql) or displaySQLError(
                'Comment Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
            );
		}
	}

    // Delete Confirmation
    if (isset($_POST['delcom']) && !isset($_POST['confirmed'])) {
        $show = false;
        echo '
    <div class="info-alert clearfix">
        <form action="familynews_comments.php?newsid='.$news_id.'" method="post">
            <h2>'.$LANG['js_del_comment'].'</h2>
            <p><b><i>'.$LANG['cannot_be_undone'].'</i></b></p>
            <div>
                <input type="hidden" name="id" value="'.$_POST['id'].'"/>
                <input style="float:left;" type="submit" id="delconfirm" name="delconfirm" value="'.$LANG['yes'].'"/>
                <a style="float:right;" href="familynews_comments.php?newsid='.$news_id.'">'.$LANG['cancel'].'</a>
            </div>
        </form>
    </div>';

    // Delete Comment
    } elseif (isset($_POST['delconfirm']) || isset($_POST['confirmed'])) {
        $sql = "DELETE FROM fcms_news_comments WHERE id=" . $_POST['id'];
        mysql_query($sql) or displaySQLError(
            'Delete Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
	}

    // Show Comments
    if ($show) {
	    echo '
            <h3>'.$LANG['comments'].'</h3>
            <p class="center">
                <form action="familynews_comments.php?newsid='.$news_id.'" method="post">
                    '.$LANG['add_comment'].'<br/>
                    <input type="text" name="comment" id="comment" size="50" title="'.$LANG['add_comment'].'"/>
                    <input type="submit" name="addcom" id="addcom" value="'.$LANG['add_comment'].'" class="gal_addcombtn"/>
                </form>
            </p>
            <p class="center">&nbsp;</p>';
        $sql = "SELECT c.id, comment, `date`, user 
                FROM fcms_news_comments AS c, fcms_users AS u 
                WHERE news = $news_id 
                AND c.user = u.id 
                ORDER BY `date`";
        $result = mysql_query($sql) or displaySQLError(
            'Comments Error', __FILE__ . ' [' . __LINE__ . ']', $sql, mysql_error()
        );
        if (mysql_num_rows($result) > 0) { 
            while($row = mysql_fetch_array($result)) {
                $displayname = getUserDisplayName($row['user']);
                if ($_SESSION['login_id'] == $row['user'] || checkAccess($_SESSION['login_id']) < 2) {
                    echo '
            <div class="comment_block">
                <form action="familynews_comments.php?newsid='.$news_id.'" method="post">
                    <input type="submit" name="delcom" id="delcom" value="'.$LANG['delete'].'" class="gal_delcombtn" title="'.$LANG['title_del_comment'].'"/>
                    <span>'.$row['date'].'</span><b>'.$displayname.'</b><br/>
                    '.htmlentities(stripslashes($row['comment']), ENT_COMPAT, 'UTF-8').'
                    <input type="hidden" name="id" value="'.$row['id'].'">
                </form>
            </div>';
			    } else {
				    echo '
            <div class="comment_block">
                <span>'.$row['date'].'</span><b>'.$displayname.'</b><br/>
                '.htmlentities(stripslashes($row['comment']), ENT_COMPAT, 'UTF-8').'
            </div>';
			    }
		    }
	    } else { echo "<p class=\"center\">".$LANG['no_comments']."</p>"; }
    }
} else {
	echo "<h3>".$LANG['invalid_newsid']."</h3>";
} ?>
</body>
</html>
