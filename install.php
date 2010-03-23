<!--
Family Connections - a family oriented CMS - http://www.familycms.com/

Copyright (C) 2007-09 Ryan Haudenschilt

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
-->
<?php
include_once('inc/language.php');
if (get_magic_quotes_gpc()) {
	$_REQUEST = array_map('stripslashes', $_REQUEST);
	$_GET = array_map('stripslashes', $_GET);
	$_POST = array_map('stripslashes', $_POST);
	$_COOKIE = array_map('stripslashes', $_COOKIE);
} ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title>Installation -> Family Connections</title>
<link rel="stylesheet" type="text/css" href="themes/fcms-core.css" />
<script type="text/javascript" src="inc/prototype.js"></script>
<script type="text/javascript" src="inc/livevalidation.js"></script>
</head>
<body>
<?php
if (!isset($_POST['submit1']) && !isset($_POST['submit2']) && !isset($_POST['submit3']) && !isset($_POST['submit4']) && !isset($_POST['submit5']) && file_exists('inc/config_inc.php')) {
	echo <<<HTML
    <div id="install">
        <h1>{$LANG['already_install1']}</h1>
        <form>
            <p>{$LANG['already_install2']}</p>
            <div class="clearfix">
                <div class="option">
                    <a class="nbtn" href="index.php">{$LANG['already_install3']}</a><br/><br/>
                    {$LANG['already_install4']}
                </div>
                <div class="option">
                    <a class="ybtn" href="#" onclick="$('show-install').toggle(); $('install').toggle(); document.setupform.dbhost.focus(); return false">{$LANG['already_install5']}</a><br/><br/>
                    {$LANG['already_install6']}
                </div>
            </div>
            <p>&nbsp;</p>
        </form>
        <br/>
    </div>
    <div id="show-install" style="display:none;">

HTML;
}
// Step Two
if (isset($_POST['submit1'])) {
	displayStepTwo();
// Step Three
} else if (isset($_POST['submit2'])) {
	if (empty($_POST['dbhost']) || empty($_POST['dbname']) || empty($_POST['dbuser']) || empty($_POST['dbpass'])) {
        echo <<<HTML
        <script type="text/javascript">
        Event.observe(window, 'load', function() {
            $('dbhost').focus();
        });
        </script>

HTML;
		displayStepTwo("<p class=\"error\">".$LANG['err_required']."</p>");
	} else {
		$file = fopen('inc/config_inc.php', 'w') or die("<h1>Error Creating Config File</h1>");
		$str = "<?php \$cfg_mysql_host = '".$_POST['dbhost']."'; \$cfg_mysql_db = '".$_POST['dbname']."'; \$cfg_mysql_user = '".$_POST['dbuser']."'; \$cfg_mysql_pass = '".$_POST['dbpass']."'; ?".">";
		fwrite($file, $str);
		fclose($file);
		displayStepThree();
	}
// Step Four
} else if (isset($_POST['submit3'])) {
        echo <<<HTML
        <script type="text/javascript">
        Event.observe(window, 'load', function() {
            $('sitename').focus();
        });
        </script>

HTML;
	displayStepFour();
// Step Five
} else if (isset($_POST['submit4'])) {
	if (empty($_POST['sitename']) || empty($_POST['contact'])) {
		displayStepFour("<p class=\"error\">".$LANG['err_required']."</p>");
	} else {
		include_once('inc/config_inc.php');
		mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
		mysql_select_db($cfg_mysql_db);
		mysql_query("TRUNCATE TABLE `fcms_config`") or die(mysql_error());
		$sql = "INSERT INTO `fcms_config` (`sitename`, `contact`, `section1`, `section2`, `section3`, `section4`, `current_version`) VALUES ('".addslashes($_POST['sitename'])."', '".addslashes($_POST['contact'])."', ";
		$rec = false; $docs = false; $pray = false; $none = false;
		// section1
        if (isset($_POST['sections-news'])) {
            $sql .= "'familynews', ";
        } elseif ($_POST['sections-recipes']) {
            $sql .= "'recipes', ";
            $rec = true;
        } elseif ($_POST['sections-documents']) {
            $sql .= "'documents', ";
            $docs = true;
        } elseif ($_POST['sections-prayers']) {
            $sql .= "'prayers', 'none', 'none', 'none', ";
			$pray = true;
            $none = true;
        } else {
            $sql .= "'none', 'none', 'none', 'none', ";
			$none = true;
        }
        // section2
        if (isset($_POST['sections-recipes']) && !$rec) {
            $sql .= "'recipes', ";
        } elseif (isset($_POST['sections-documents']) && !$docs) {
            $sql .= "'documents', ";
            $docs = true;
        } elseif (isset($_POST['sections-prayers']) && !$pray) {
            $sql .= "'prayers', 'none', 'none', ";
			$pray = true;
            $none = true;
        } elseif (!$none) {
            $sql .= "'none', 'none', 'none', ";
			$none = true;
        }
        // section3
        if (isset($_POST['sections-documents']) && !$docs) {
            $sql .= "'documents', ";
        } elseif (isset($_POST['sections-prayers']) && !$pray) {
            $sql .= "'prayers', 'none', ";
			$pray = true;
            $none = true;
        } elseif (!$none) {
            $sql .= "'none', 'none', ";
			$none = true;
        }
        // section4
        if (isset($_POST['sections-prayers']) && !$pray) {
            $sql .= "'prayers', ";
        } elseif (!$none) {
            $sql .= "'none', ";
        }
		$sql .= "'Family Connections 2.1')";
		mysql_query($sql) or die($sql . "<br/><br/>" . mysql_error());
		displayStepFive();
	}
// Finish
} else if (isset($_POST['submit5'])) {
	if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['fname']) || empty($_POST['lname']) || empty($_POST['email'])) {
		displayStepFive("<p class=\"error\">".$LANG['err_required']."</p>");
	} else {
		setupDatabase($_POST['fname'], $_POST['lname'], $_POST['email'], $_POST['username'], $_POST['password']);
	}
} else {
	displayStepOne();
	echo "</div>";
} ?>
</body>
</html>
<?php
function is__writable($path) {
//will work in despite of Windows ACLs bug
//NOTE: use a trailing slash for folders!!!
//see http://bugs.php.net/bug.php?id=27609
//see http://bugs.php.net/bug.php?id=30931
    if ($path{strlen($path)-1}=='/') // recursively return a temporary file path
        return is__writable($path.uniqid(mt_rand()).'.tmp');
    else if (@is_dir($path))
        return is__writable($path.'/'.uniqid(mt_rand()).'.tmp');
    // check tmp file for read/write capabilities
    $rm = file_exists($path);
    $f = @fopen($path, 'a');
    if ($f===false)
        return false;
    fclose($f);
    if (!$rm)
        unlink($path);
    return true;
}
function displayStepOne () {
	global $LANG; $inc = false; $avatar = false; $photos = false; $up = false; ?>
	<div id="column">
	    <h1><?php echo $LANG['install']; ?> Family Connections</h1>
		<h2>Pre-Installation Check</h2>
	    <form class="nofields" action="install.php" method="post">
		<div style="text-align:center">Step 1 of 5</div><div class="progress"><div style="width:20%"></div></div>
		<div><b>Checking Folder Permissions</b></div>
		<div><div class="dir">inc/</div> <div class="status"><?php if (is__writable('inc/')) { echo "<span class=\"ok\">OK</span>"; $inc = true; } else { echo "<span class=\"bad\">BAD</span>"; } ?></div></div>
		<div style="clear:both;"></div>
		<div><div class="dir">gallery/avatar/</div> <div class="status"><?php if (is__writable('gallery/avatar/')) { echo "<span class=\"ok\">OK</span>"; $avatar = true; } else { echo "<span class=\"bad\">BAD</span>"; } ?></div></div>
		<div style="clear:both;"></div>
		<div><div class="dir">gallery/documents/</div> <div class="status"><?php if (is__writable('gallery/documents/')) { echo "<span class=\"ok\">OK</span>"; $avatar = true; } else { echo "<span class=\"bad\">BAD</span>"; } ?></div></div>
		<div style="clear:both;"></div>
		<div><div class="dir">gallery/photos/</div> <div class="status"><?php if (is__writable('gallery/photos/')) { echo "<span class=\"ok\">OK</span>"; $photos = true; } else { echo "<span class=\"bad\">BAD</span>"; } ?></div></div>
		<div style="clear:both;"></div>
		<div><div class="dir">gallery/upimages/</div> <div class="status"><?php if (is__writable('gallery/upimages/')) { echo "<span class=\"ok\">OK</span>"; $up = true; } else { echo "<span class=\"bad\">BAD</span>"; } ?></div></div>
		<div style="clear:both;"></div><?php 
		if ($inc && $avatar && $photos && $up) { ?>
		<div>Your site is ready to be installed.  Please proceed to the next step.</div>
		<p style="text-align:right;"><input id="submit" name="submit1" type="submit"  value="<?php echo $LANG['next']; ?> >>"/></p>
        <div class="clear"></div>
		<?php } else { ?>
		<div>Unfortunatly your site is not ready to be installed.  Please make sure that the folders above exist and have the <a href="http://www.familycms.com/wiki/FAQCHMOD">proper permissions set</a>.</div>
		<?php } ?>
        </form>
	</div>
<?php
}
function displayStepTwo ($error = '0') {
	global $LANG; ?>
	<div id="column">
        <h1><?php echo $LANG['install']; ?> Family Connections</h1>
		<h2><?php echo $LANG['db_info']; ?></h2>
		<div style="text-align:center">Step 2 of 5</div><div class="progress"><div style="width:40%"></div></div>
		<?php if ($error !== '0') { echo $error; } ?>
        <form action="install.php" method="post">
		<div>
            <div class="field-label"><label for="dbhost"><b><?php echo $LANG['db_host']; ?></b> <span class="req">*</span></label></div>
            <div class="field-widget">
                <input type="text" name="dbhost" id="dbhost" title="<?php echo $LANG['title_db_host']; ?>"/>
		        <div><?php echo $LANG['db_host_desc1']." <i>".$LANG['db_host_desc2']."</i> ".$LANG['db_host_desc3']?></div>
            </div>
        </div>
		<script type="text/javascript">
			var fdbhost = new LiveValidation('dbhost', { onlyOnSubmit: true });
			fdbhost.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_ins_sorry_req']; ?>"});
		</script>
		<div>
            <div class="field-label"><label for="dbname"><b><?php echo $LANG['db_name']; ?></b> <span class="req">*</span></label></div>
            <div class="field-widget">
                <input type="text" name="dbname" id="dbname" title="<?php echo $LANG['title_db_name']; ?>"/>
		        <div><?php echo $LANG['db_name_desc']; ?> Family Connections.</div>
            </div>
        </div>
		<script type="text/javascript">
			var fdbname = new LiveValidation('dbname', { onlyOnSubmit: true });
			fdbname.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_ins_sorry_req']; ?>"});
		</script>
		<div>
            <div class="field-label"><label for="dbuser"><b><?php echo $LANG['db_uname']; ?></b> <span class="req">*</span></label></div>
            <div class="field-widget">
                <input type="text" name="dbuser" id="dbuser" title="<?php echo $LANG['title_db_uname']; ?>"/>
		        <div><?php echo $LANG['db_uname_desc']; ?></div>
            </div>
        </div>
		<script type="text/javascript">
			var fdbuser = new LiveValidation('dbuser', { onlyOnSubmit: true });
			fdbuser.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_bad_dbuser']; ?>"});
		</script> 	
		<div>
            <div class="field-label"><label for="dbpass"><b><?php echo $LANG['db_pass']; ?></b> <span class="req">*</span></label></div>
            <div class="field-widget">
                <input type="password" name="dbpass" id="dbpass" title="<?php echo $LANG['title_db_pass']; ?>"/>
                <div><?php echo $LANG['db_pass_desc']; ?></div>
            </div>
        </div>
		<script type="text/javascript">
			var fdbpass = new LiveValidation('dbpass', { onlyOnSubmit: true });
			fdbpass.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_bad_pass']; ?>"});
		</script>
		<p style="text-align:right;"><input id="submit" name="submit2" type="submit"  value="<?php echo $LANG['next']; ?> >>"/></p>
        <div class="clear"></div>
        </form>
	</div>
<?php
}
function displayStepThree () {
	include_once('inc/config_inc.php');
	global $LANG; ?>
	<div id="column">
        <h1><?php echo $LANG['install']; ?> Family Connections</h1>
        <form class="nofields" action="install.php" method="post">
		<h2>Checking Database Connection</h2>
		<div style="text-align:center">Step 3 of 5</div><div class="progress"><div style="width:60%"></div></div>
		<div>Attempting to connect to database <i><?php echo $cfg_mysql_db; ?></i> on <i><?php echo $cfg_mysql_host; ?></i> using user <i><?php echo $cfg_mysql_user; ?></i>...</div>
		<?php
		$connection = @mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
		if (!$connection) {
			die("<h3 class=\"bad\">Uh-Oh!</h3><div>A connection to the database could not be made.  Please shut down your browser and then re-run the installation.</div>");
		} else {
			mysql_select_db($cfg_mysql_db) or die("<h1>Error</h1><p><b>Connection made, but database could not be found!</b></p>" . mysql_error());
			echo "<h3>Awesome!</h3><div>A connection was successfully made to the database.  Please proceed to the next step.</div>";
			mysql_query("DROP TABLE IF EXISTS `fcms_config`") or die("<h1>Error</h1><p><b>Could not drop `fcms_config` table.</b></p>" . mysql_error());
            $sql = "CREATE TABLE `fcms_config` (
                        `sitename` VARCHAR(50) NOT NULL DEFAULT 'My Site', 
                        `contact` VARCHAR(50) NOT NULL DEFAULT 'nobody@yoursite.com', 
                        `section1` VARCHAR(20) NOT NULL DEFAULT 'familynews', 
                        `section2` VARCHAR(20) NOT NULL DEFAULT 'recipes', 
                        `section3` VARCHAR(20) NOT NULL DEFAULT 'documents', 
                        `section4` VARCHAR(20) NOT NULL DEFAULT 'prayers', 
                        `current_version` VARCHAR(50) NOT NULL DEFAULT 'Family Connections', 
                        `auto_activate` TINYINT(1) NOT NULL DEFAULT 0, 
                        `full_size_photos` TINYINT(1) NOT NULL DEFAULT 0,
                        `site_off` TINYINT(1) NOT NULL DEFAULT '0'
                    ) 
                    ENGINE=InnoDB DEFAULT CHARSET=utf8";
			mysql_query($sql) or die(mysql_error());
		} ?>
		<p style="text-align:right;"><input id="submit" name="submit3" type="submit"  value="<?php echo $LANG['next']; ?> >>"/></p>
        <div class="clear"></div>
        </form>
	</div>
<?php
}
function displayStepFour ($error = '0') {
	global $LANG; ?>
	<div id="column">
	    <h1><?php echo $LANG['install']; ?> Family Connections</h1>
		<h2><?php echo $LANG['site_info']; ?></h2>
		<div style="text-align:center">Step 4 of 5</div><div class="progress"><div style="width:80%"></div></div>
	    <form action="install.php" method="post">
		<div>
            <div class="field-label"><label for="sitename"><b><?php echo $LANG['site_name'];?></b> <span class="req">*</span></label>
            <div class="field-widget big">
                <input type="text" name="sitename" id="sitename" title="<?php echo $LANG['title_site_name']; ?>"/>
		        <div><?php echo $LANG['site_name_desc']; ?></div>
            </div>
        </div>
		<script type="text/javascript">
			var fsitename = new LiveValidation('sitename', { onlyOnSubmit: true });
			fsitename.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_bad_sitename']; ?>"});
		</script>
		<div>
            <div class="field-label"><label for="contact"><b><?php echo $LANG['contact']; ?></b> <span class="req">*</span></label></div>
            <div class="field-widget big">
                <input type="text" name="contact" id="contact" title="<?php echo $LANG['title_contact']; ?>"/>
		        <div><?php echo $LANG['contact_desc']; ?></div>
            </div>
        </div>
		<script type="text/javascript">
			var fcontact = new LiveValidation('contact', { onlyOnSubmit: true });
			fcontact.add( Validate.Presence, { failureMessage: "<?php echo $LANG['lv_ins_sorry_req']; ?>" } );
			fcontact.add( Validate.Email, { failureMessage: "<?php echo $LANG['lv_bad_email']; ?>" } );
			fcontact.add( Validate.Length, { minimum: 10 } );
		</script>
		<div>
            <div class="field-label"><label><b><?php echo $LANG['sections']; ?></b></label></div>
            <div>
			    <input type="checkbox" name="sections-news" id="sections-news" value="familynews"/>
                <label for="sections-news"><?php echo $LANG['link_familynews']; ?></label><br/>
			    <input type="checkbox" name="sections-recipes" id="sections-recipes" value="recipes"/>
                <label for="sections-recipes"><?php echo $LANG['link_recipes']; ?></label><br/>
			    <input type="checkbox" name="sections-documents" id="sections-documents" value="documents"/>
                <label for="sections-documents"><?php echo $LANG['link_documents']; ?></label><br/>
			    <input type="checkbox" name="sections-prayers" id="sections-prayers" value="prayerconcerns"/>
                <label for="sections-prayers"><?php echo $LANG['link_prayers']; ?></label>
		    </div>
        </div>
		<p><?php echo $LANG['sections_desc']; ?></p>
		<p style="text-align:right;"><input id="submit" name="submit4" type="submit"  value="<?php echo $LANG['next']; ?> >>"/></p>
        <div class="clear"></div>
        </form>
	</div>
<?php
}
function displayStepFive ($error = '0') {
	global $LANG; ?>
	<div id="column">
        <h1><?php echo $LANG['install']; ?> Family Connections</h1>
		<h2><?php echo $LANG['admin_account']; ?></h2>
		<div style="text-align:center">Step 5 of 5</div><div class="progress"><div style="width:100%"></div></div>
        <form action="install.php" method="post">
		<p><?php echo $LANG['admin_desc1']; ?></p>
		<p><?php echo $LANG['admin_desc2']; ?></p>
		<div>
            <div class="field-label"><label for="username"><b><?php echo $LANG['username']; ?></b> <span class="req">*</span></label></div>
            <div class="field-widget"><input type="text" name="username" id="username" title="<?php echo $LANG['title_uname']; ?>"/></div>
        </div>
		<script type="text/javascript">
			var funame = new LiveValidation('username', { onlyOnSubmit: true });
			funame.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_ins_sorry_req']; ?>"});
		</script>
		<div>
            <div class="field-label"><label for="password"><b><?php echo $LANG['password']; ?></b> <span class="req">*</span></label></div>
            <div class="field-widget"><input type="password" name="password" id="password" title="<?php echo $LANG['title_pass']; ?>"/></div>
        </div>
		<script type="text/javascript">
			var fpass = new LiveValidation('password', { onlyOnSubmit: true });
			fpass.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_bad_pass']; ?>"});
		</script>
		<div>
            <div class="field-label"><label for="fname"><b><?php echo $LANG['first_name']; ?></b> <span class="req">*</span></label></div>
            <div class="field-widget"><input type="text" name="fname" id="fname" title="<?php echo $LANG['title_fname']; ?>"/></div>
        </div>
		<script type="text/javascript">
			var ffname = new LiveValidation('fname', { onlyOnSubmit: true });
			ffname.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_ins_sorry_req']; ?>"});
		</script>
		<div>
            <div class="field-label"><label for="lname"><b><?php echo $LANG['last_name']; ?></b> <span class="req">*</span></label></div>
            <div class="field-widget"><input type="text" name="lname" id="lname" title="<?php echo $LANG['title_lname']; ?>"/></div>
        </div>
		<script type="text/javascript">
			var flname = new LiveValidation('lname', { onlyOnSubmit: true });
			flname.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_ins_sorry_req']; ?>"});
		</script>
		<div>
            <div class="field-label"><label for="email"><b><?php echo $LANG['email_address']; ?></b> <span class="req">*</span></label></div>
            <div class="field-widget"><input type="text" name="email" id="email" title="<?php echo $LANG['title_email']; ?>"/></div>
        </div>
		<script type="text/javascript">
			var femail = new LiveValidation('email', { onlyOnSubmit: true });
			femail.add( Validate.Presence, { failureMessage: "<?php echo $LANG['lv_ins_sorry_req']; ?>" } );
			femail.add( Validate.Email, { failureMessage: "<?php echo $LANG['lv_bad_email']; ?>" } );
			femail.add( Validate.Length, { minimum: 10 } );
		</script>
		<p style="text-align:right;"><input id="submit" name="submit5" type="submit"  value="<?php echo $LANG['next']; ?> >>"/></p>
        <div class="clear"></div>
        </form>
	</div>
<?php
}
function setupDatabase ($fname, $lname, $email, $username, $password) {
	include_once('inc/config_inc.php');
	include_once('inc/util_inc.php');
	global $LANG;
	$password = md5($password);
	$connection = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
	if (!$connection) {
		die("<h1>Connection Error [" . __FILE__ . __LINE__ . "]</h1>" . mysql_error());
	} else {
		mysql_select_db($cfg_mysql_db) or die("<h1>Error</h1><p><b>Database could not be found!</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_chat_users`") or die("<h1>Error</h1><p><b>Could not drop 'fcms_chat' table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_chat_messages`") or die("<h1>Error</h1><p><b>Could not drop 'fcms_chat_messages' table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_address`") or die("<h1>Error</h1><p><b>Could not drop `fcms_address` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_privatemsg`") or die("<h1>Error</h1><p><b>Could not drop `fcms_privatemsg` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_documents`") or die("<h1>Error</h1><p><b>Could not drop `fcms_documents` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_calendar`") or die("<h1>Error</h1><p><b>Could not drop `fcms_calendar` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_gallery_comments`") or die("<h1>Error</h1><p><b>Could not drop `fcms_gallery_comments` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_gallery_photos_tags`") or die("<h1>Error</h1><p><b>Could not drop `fcms_gallery_photos_tags` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_gallery_photos`") or die("<h1>Error</h1><p><b>Could not drop `fcms_gallery_photos` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_gallery_category`") or die("<h1>Error</h1><p><b>Could not drop `fcms_gallery_category` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_news_comments`") or die("<h1>Error</h1><p><b>Could not drop `fcms_news_comments` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_news`") or die("<h1>Error</h1><p><b>Could not drop `fcms_news` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_poll_votes`") or die("<h1>Error</h1><p><b>Could not drop `fcms_poll_users` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_poll_options`") or die("<h1>Error</h1><p><b>Could not drop `fcms_poll_options` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_polls`") or die("<h1>Error</h1><p><b>Could not drop `fcms_polls` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_prayers`") or die("<h1>Error</h1><p><b>Could not drop `fcms_prayers` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_board_posts`") or die("<h1>Error</h1><p><b>Could not drop `fcms_board_posts` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_board_threads`") or die("<h1>Error</h1><p><b>Could not drop `fcms_board_threads` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_recipes`") or die("<h1>Error</h1><p><b>Could not drop `fcms_recipes` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_user_awards`") or die("<h1>Error</h1><p><b>Could not drop `fcms_user_awards` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_user_settings`") or die("<h1>Error</h1><p><b>Could not drop `fcms_user_settings` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_users`") or die("<h1>Error</h1><p><b>Could not drop `fcms_users` table.</b></p>" . mysql_error());
		mysql_query("SET NAMES utf8") or die("<h1>Error</h1><p><b>Could not set encoding</b></p>" . mysql_error());
        // create users
        $sql = "CREATE TABLE `fcms_users` (
                    `id` INT(25) NOT NULL AUTO_INCREMENT, 
                    `access` TINYINT(1) NOT NULL DEFAULT '3', 
                    `activity` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    `joindate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                    `fname` VARCHAR(25) NOT NULL DEFAULT 'fname', 
                    `lname` VARCHAR(25) NOT NULL DEFAULT 'lname', 
                    `email` VARCHAR(50) NOT NULL DEFAULT 'me@mail.com', 
                    `birthday` DATE NOT NULL DEFAULT '0000-00-00', 
                    `username` VARCHAR(25) NOT NULL DEFAULT '0', 
                    `password` VARCHAR(255) NOT NULL DEFAULT '0', 
                    `avatar` VARCHAR(25) NOT NULL DEFAULT 'no_avatar.jpg', 
                    `activate_code` CHAR(13) NULL, 
                    `activated` TINYINT(1) NOT NULL DEFAULT '0', 
                    `login_attempts` TINYINT(1) NOT NULL DEFAULT '0', 
                    `locked` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    PRIMARY KEY (`id`), 
                    UNIQUE KEY `username` (`username`)
                )
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
		mysql_query($sql) or die(mysql_error());
        // insert users
		$sql = "INSERT INTO `fcms_users` 
                    (`id`, `access`, `joindate`, `fname`, `lname`, `email`, `username`, `password`, `activated`) 
                VALUES 
                    (1, 1, NOW(), '".addslashes($fname)."', '".addslashes($lname)."', '".addslashes($email)."', '".addslashes($username)."', '$password', 1)";
        mysql_query($sql) or die(mysql_error());
        // create user_settings
        $sql = "CREATE TABLE `fcms_user_settings` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `user` INT(11) NOT NULL, 
                    `theme` VARCHAR(25) NOT NULL DEFAULT 'default', 
                    `boardsort` SET('ASC', 'DESC') NOT NULL DEFAULT 'ASC', 
                    `showavatar` TINYINT(1) NOT NULL DEFAULT '1', 
                    `displayname` SET('1','2','3') NOT NULL DEFAULT '1', 
                    `frontpage` SET('1','2') NOT NULL DEFAULT '1', 
                    `timezone` set('-12 hours', '-11 hours', '-10 hours', '-9 hours', '-8 hours', '-7 hours', '-6 hours', '-5 hours', '-4 hours', '-3 hours -30 minutes', '-3 hours', '-2 hours', '-1 hours', '-0 hours', '+1 hours', '+2 hours', '+3 hours', '+3 hours +30 minutes', '+4 hours', '+4 hours +30 minutes', '+5 hours', '+5 hours +30 minutes', '+6 hours', '+7 hours', '+8 hours', '+9 hours', '+9 hours +30 minutes', '+10 hours', '+11 hours', '+12 hours') NOT NULL DEFAULT '-5 hours', 
                    `dst` TINYINT(1) NOT NULL DEFAULT '0', 
                    `email_updates` TINYINT(1) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `user_ind` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter user_settings
		$sql = "ALTER TABLE `fcms_user_settings` 
                ADD CONSTRAINT `fcms_user_stgs_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // insert user_settings
		$sql = "INSERT INTO `fcms_user_settings` (`id`, `user`) 
                VALUES (NULL, 1)";
        mysql_query($sql) or die(mysql_error());
        // create address
		$sql = "CREATE TABLE `fcms_address` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    `entered_by` INT(11) NOT NULL DEFAULT '0', 
                    `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                    `address` VARCHAR(50) DEFAULT NULL, 
                    `city` VARCHAR(50) DEFAULT NULL, 
                    `state` VARCHAR(50) DEFAULT NULL, 
                    `zip` VARCHAR(10) DEFAULT NULL, 
                    `home` VARCHAR(20) DEFAULT NULL, 
                    `work` VARCHAR(20) DEFAULT NULL, 
                    `cell` VARCHAR(20) DEFAULT NULL, 
                    PRIMARY KEY (`id`), 
                    KEY `user_ind` (`user`), 
                    KEY `ent_ind` (`entered_by`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter address
		$sql = "ALTER TABLE `fcms_address` 
                ADD CONSTRAINT `fcms_address_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // insert address
		$sql = "INSERT INTO `fcms_address` 
                    (`id`, `user`, `entered_by`, `address`, `city`, `state`, `zip`, `home`, `work`, `cell`) 
                VALUES 
                    (NULL, 1, 1, '".addslashes($address)."', '".addslashes($city)."', '".addslashes($state)."', '".addslashes($zip)."', '".addslashes($home)."', '".addslashes($work)."', '".addslashes($cell)."')";
        mysql_query($sql) or die(mysql_error());
        // create calendar
		$sql = "CREATE TABLE `fcms_calendar` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `date` DATE NOT NULL DEFAULT '0000-00-00', 
                    `date_added` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    `title` VARCHAR(50) NOT NULL DEFAULT 'MyDate', 
                    `desc` TEXT, 
                    `created_by` INT(11) NOT NULL DEFAULT '0', 
                    `type` SET('Birthday','Anniversary','Holiday','Other') NOT NULL DEFAULT 'Other', 
                    `private` TINYINT(1) NOT NULL DEFAULT '0', 
                    PRIMARY KEY  (`id`), 
                    KEY `by_ind` (`created_by`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
		// alter calendar
        $sql = "ALTER TABLE `fcms_calendar` 
                ADD CONSTRAINT `fcms_calendar_ibfk_1` 
                FOREIGN KEY (`created_by`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // insert calendar
		$sql = "INSERT INTO `fcms_calendar` 
                    (`id`, `date`, `title`, `desc`, `created_by`, `type`) 
                VALUES 
                    (NULL, '$birthday', '".addslashes($fname)." ".addslashes($lname)."', NULL, 1, 'Birthday'), 
                    (NULL, '2007-12-25', 'Christmas', NULL, 1, 'Holiday'), 
                    (NULL, '2007-02-14', 'Valentine''s Day', NULL, 1, 'Holiday'), 
                    (NULL, '2007-01-01', 'New Year''s Day', NULL, 1, 'Holiday'), 
                    (NULL, '2007-07-04', 'Independence Day', NULL, 1, 'Holiday'), 
                    (NULL, '2007-02-02', 'Groundhog Day', NULL, 1, 'Holiday'), 
                    (NULL, '2007-03-17', 'St. Patrick''s Day', NULL, 1, 'Holiday'), 
                    (NULL, '2007-04-01', 'April Fools Day', NULL, 1, 'Holiday'), 
                    (NULL, '2007-10-31', 'Halloween', NULL, 1, 'Holiday')";
        mysql_query($sql) or die(mysql_error());
        // create gallery_category
		$sql = "CREATE TABLE `fcms_gallery_category` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `name` VARCHAR(50) NOT NULL DEFAULT 'category', 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `user_ind` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter gallery_category
		$sql = "ALTER TABLE `fcms_gallery_category` 
                ADD CONSTRAINT `fcms_gallery_category_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // create gallery_photos
		$sql = "CREATE TABLE `fcms_gallery_photos` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    `filename` VARCHAR(25) NOT NULL DEFAULT 'noimage.gif', 
                    `caption` TEXT, 
                    `category` INT(11) NOT NULL DEFAULT '0', 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    `views` SMALLINT(6) NOT NULL DEFAULT '0', 
                    `votes` SMALLINT(6) NOT NULL DEFAULT '0', 
                    `rating` FLOAT NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `cat_ind` (`category`), 
                    KEY `user_ind` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter gallery_photos
		$sql = "ALTER TABLE `fcms_gallery_photos` 
                ADD CONSTRAINT `fcms_gallery_photos_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE, 
                ADD CONSTRAINT `fcms_gallery_photos_ibfk_2` 
                FOREIGN KEY (`category`) 
                REFERENCES `fcms_gallery_category` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // create gallery_comments
		$sql = "CREATE TABLE `fcms_gallery_comments` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `photo` INT(11) NOT NULL DEFAULT '0', 
                    `comment` TEXT NOT NULL, 
                    `date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `photo_ind` (`photo`), 
                    KEY `user_ind` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter gallery_comments
		$sql = "ALTER TABLE `fcms_gallery_comments` 
                ADD CONSTRAINT `fcms_gallery_comments_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE, 
                ADD CONSTRAINT `fcms_gallery_comments_ibfk_2` 
                FOREIGN KEY (`photo`) 
                REFERENCES `fcms_gallery_photos` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // create gallery_photos_tags
		$sql = "CREATE TABLE `fcms_gallery_photos_tags` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    `photo` INT(11) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `tag_photo_ind` (`photo`), 
                    KEY `tag_user_ind` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter gallery_photos_tags
		$sql = "ALTER TABLE `fcms_gallery_photos_tags` 
                ADD CONSTRAINT `fcms_gallery_photos_tags_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE, 
                ADD CONSTRAINT `fcms_gallery_photos_tags_ibfk_2` 
                FOREIGN KEY (`photo`) 
                REFERENCES `fcms_gallery_photos` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
		if (usingFamilyNews()) {
            // create news
			$sql = "CREATE TABLE `fcms_news` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT, 
                        `title` VARCHAR(50) NOT NULL DEFAULT '', 
                        `news` TEXT NOT NULL, 
                        `user` INT(11) NOT NULL DEFAULT '0', 
                        `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                        PRIMARY KEY (`id`), 
                        KEY `userindx` (`user`)
                    ) 
                    ENGINE=InnoDB DEFAULT CHARSET=utf8";
            mysql_query($sql) or die(mysql_error());
            // alter news
			$sql = "ALTER TABLE `fcms_news` 
                    ADD CONSTRAINT `fcms_news_ibfk_1` 
                    FOREIGN KEY (`user`) 
                    REFERENCES `fcms_users` (`id`) 
                    ON DELETE CASCADE";
            mysql_query($sql) or die(mysql_error());
            // create news_comments
			$sql = "CREATE TABLE `fcms_news_comments` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT, 
                        `news` INT(11) NOT NULL DEFAULT '0', 
                        `comment` TEXT NOT NULL, 
                        `date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                        `user` INT(11) NOT NULL DEFAULT '0', 
                        PRIMARY KEY (`id`), 
                        KEY `photo_ind` (`news`), 
                        KEY `user_ind` (`user`)
                    ) 
                    ENGINE=InnoDB DEFAULT CHARSET=utf8";
            mysql_query($sql) or die(mysql_error());
            // alter news_comments
			$sql = "ALTER TABLE `fcms_news_comments` 
                    ADD CONSTRAINT `fcms_news_comments_ibfk_2` 
                    FOREIGN KEY (`user`) 
                    REFERENCES `fcms_users` (`id`) 
                    ON DELETE CASCADE, 
                    ADD CONSTRAINT `fcms_news_comments_ibfk_1` 
                    FOREIGN KEY (`news`) 
                    REFERENCES `fcms_news` (`id`) 
                    ON DELETE CASCADE";
            mysql_query($sql) or die(mysql_error());
		}
        // create polls
		$sql = "CREATE TABLE `fcms_polls` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `question` TEXT NOT NULL, 
                    `started` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    PRIMARY KEY  (`id`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // insert poll
		$sql = "INSERT INTO `fcms_polls` (`id`, `question`, `started`) 
                VALUES (NULL, 'Family Connections software is...', NOW())";
        mysql_query($sql) or die(mysql_error());
        // create poll_options
		$sql = "CREATE TABLE `fcms_poll_options` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `poll_id` INT(11) NOT NULL DEFAULT '0', 
                    `option` TEXT NOT NULL, 
                    `votes` INT(11) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `pollid_ind` (`poll_id`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter poll_options
		$sql = "ALTER TABLE `fcms_poll_options` 
                ADD CONSTRAINT `fcms_poll_options_ibfk_1` 
                FOREIGN KEY (`poll_id`) 
                REFERENCES `fcms_polls` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // insert poll_options
		$sql = "INSERT INTO `fcms_poll_options` (`id`, `poll_id`, `option`, `votes`) 
                VALUES 
                    (NULL, 1, 'Easy to use!', 0), 
                    (NULL, 1, 'Visually appealing!', 0), 
                    (NULL, 1, 'Just what our family needed!', 0)";
        mysql_query($sql) or die(mysql_error());
        // create poll_votes
		$sql = "CREATE TABLE `fcms_poll_votes` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    `option` INT(11) NOT NULL DEFAULT '0', 
                    `poll_id` INT(11) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `user_ind` (`user`), 
                    KEY `option_ind` (`option`)
                    KEY `poll_id_ind` (`poll_id`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter poll_votes
		$sql = "ALTER TABLE `fcms_poll_votes` 
                ADD CONSTRAINT `fcms_poll_votes_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE, 
                ADD CONSTRAINT `fcms_poll_votes_ibfk_2` 
                FOREIGN KEY (`option`) 
                REFERENCES `fcms_poll_options` (`id`) 
                ON DELETE CASCADE 
                ADD CONSTRAINT `fcms_poll_votes_ibfk_3` 
                FOREIGN KEY (`poll_id`) 
                REFERENCES `fcms_polls` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
		if (usingPrayers()) {
            // create prayers
			$sql = "CREATE TABLE `fcms_prayers` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT, 
                        `for` VARCHAR(50) NOT NULL DEFAULT '', 
                        `desc` TEXT NOT NULL, 
                        `user` INT(11) NOT NULL DEFAULT '0', 
                        `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                        PRIMARY KEY (`id`), 
                        KEY `userindx` (`user`)
                    ) 
                    ENGINE=InnoDB DEFAULT CHARSET=utf8";
            mysql_query($sql) or die(mysql_error());
            // alter prayers
			$sql = "ALTER TABLE `fcms_prayers` 
                    ADD CONSTRAINT `fcms_prayers_ibfk_1` 
                    FOREIGN KEY (`user`) 
                    REFERENCES `fcms_users` (`id`) 
                    ON DELETE CASCADE";
            mysql_query($sql) or die(mysql_error());
		}
        // create board_threads
		$sql = "CREATE TABLE `fcms_board_threads` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `subject` VARCHAR(50) NOT NULL DEFAULT 'Subject', 
                    `started_by` INT(11) NOT NULL DEFAULT '0', 
                    `updated` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    `updated_by` INT(11) NOT NULL DEFAULT '0', 
                    `views` SMALLINT(6) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `start_ind` (`started_by`), 
                    KEY `up_ind` (`updated_by`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter board_threads
		$sql = "ALTER TABLE `fcms_board_threads` 
                ADD CONSTRAINT `fcms_threads_ibfk_1` 
                FOREIGN KEY (`started_by`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE, 
                ADD CONSTRAINT `fcms_threads_ibfk_2` 
                FOREIGN KEY (`updated_by`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // insert board_threads
		$sql = "INSERT INTO `fcms_board_threads` (`id`, `subject`, `started_by`, `updated`, `updated_by`, `views`) 
                VALUES (1, '".$LANG['welcome']."', 1, NOW(), 1, 0)";
        mysql_query($sql) or die(mysql_error());
        // create board_posts
		$sql = "CREATE TABLE `fcms_board_posts` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    `thread` INT(11) NOT NULL DEFAULT '0', 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    `post` TEXT NOT NULL, 
                    PRIMARY KEY (`id`), 
                    KEY `thread_ind` (`thread`), 
                    KEY `user_ind` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // altert board_posts
	    $sql = "ALTER TABLE `fcms_board_posts` 
                ADD CONSTRAINT `fcms_posts_ibfk_1` 
                FOREIGN KEY (`thread`) 
                REFERENCES `fcms_board_threads` (`id`) 
                ON DELETE CASCADE, 
                ADD CONSTRAINT `fcms_posts_ibfk_2` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // insert board_posts
		$sql = "INSERT INTO `fcms_board_posts` (`id`, `date`, `thread`, `user`, `post`) 
                VALUES (NULL, NOW(), 1, 1, '".$LANG['welcome_post']."')";
        mysql_query($sql) or die(mysql_error());
		if (usingRecipes()) {
            // create recipes
			$sql = "CREATE TABLE `fcms_recipes` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT, 
                        `category` VARCHAR(50) NOT NULL, 
                        `name` VARCHAR(50) NOT NULL DEFAULT 'My Recipe', 
                        `recipe` TEXT NOT NULL, 
                        `user` INT(11) NOT NULL, 
                        `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                        PRIMARY KEY (`id`)
                    ) 
                    ENGINE=InnoDB DEFAULT CHARSET=utf8";
            mysql_query($sql) or die(mysql_error());
            // alter recipes
			$sql = "ALTER TABLE `fcms_recipes` 
                    ADD CONSTRAINT `fcms_recipes_ibfk_1` 
                    FOREIGN KEY (`user`) 
                    REFERENCES `fcms_users` (`id`) 
                    ON DELETE CASCADE";
            mysql_query($sql) or die(mysql_error());
		}
        // create privatemsg
		$sql = "CREATE TABLE `fcms_privatemsg` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `to` INT(11) NOT NULL, 
                    `from` INT(11) NOT NULL, 
                    `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    `title` VARCHAR(50) NOT NULL DEFAULT 'PM Title', 
                    `msg` TEXT, 
                    `read` TINYINT(1) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `to_ind` (`to`), 
                    KEY `from_ind` (`from`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter privatemsg
		$sql = "ALTER TABLE `fcms_privatemsg` 
                ADD CONSTRAINT `fcms_privatemsg_ibfk_1` 
                FOREIGN KEY (`to`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE, 
                ADD CONSTRAINT `fcms_privatemsg_ibfk_2` 
                FOREIGN KEY (`from`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
		if (usingDocuments()) {
            // create documents
			$sql = "CREATE TABLE `fcms_documents` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT, 
                        `name` VARCHAR(50) NOT NULL, 
                        `description` TEXT NOT NULL, 
                        `user` INT(11) NOT NULL, 
                        `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                        PRIMARY KEY (`id`)
                    ) 
                    ENGINE=InnoDB DEFAULT CHARSET=utf8";
            mysql_query($sql) or die(mysql_error());
            // alter documents
			$sql = "ALTER TABLE `fcms_documents` 
                    ADD CONSTRAINT `fcms_documents_ibfk_1` 
                    FOREIGN KEY (`user`) 
                    REFERENCES `fcms_users` (`id`) 
                    ON DELETE CASCADE";
            mysql_query($sql) or die(mysql_error());
		}
        // create chat_users
		$sql = "CREATE TABLE `fcms_chat_users` (
                    `user_name` VARCHAR(64) DEFAULT NULL,
                    `time` DATETIME NOT NULL
                ) 
                ENGINE=INNODB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // create chat_messages
		$sql = "CREATE TABLE `fcms_chat_messages` (
                    `message_id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `chat_id` INT(11) NOT NULL DEFAULT '0', 
                    `user_id` INT(11) NOT NULL DEFAULT '0', 
                    `user_name` VARCHAR(64) DEFAULT NULL, 
                    `message` TEXT, 
                    `post_time` DATETIME DEFAULT NULL, 
                    PRIMARY KEY (`message_id`)
                ) 
                ENGINE=INNODB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // create user_awards
		$sql = "CREATE TABLE `fcms_user_awards` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    `type` VARCHAR(20) NOT NULL DEFAULT '0', 
                    `value` SMALLINT(4) NOT NULL DEFAULT '0', 
                    `count` SMALLINT(4) NOT NULL default '0', 
                    PRIMARY KEY (`id`), 
                    KEY `user` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter user_awards
		$sql = "ALTER TABLE `fcms_user_awards` 
                ADD CONSTRAINT `fcms_user_awards_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // insert user_awards
		$sql = "INSERT INTO `fcms_user_awards` 
                    (`id`, `user`, `type`, `value`, `count`) 
                VALUES 
                    (1, 1, 'top5poster', 1, 0), 
                    (2, 1, 'top5poster', 2, 0), 
                    (3, 1, 'top5poster', 3, 0), 
                    (4, 1, 'top5poster', 4, 0), 
                    (5, 1, 'top5poster', 5, 0), 
                    (6, 1, 'topthreadstarter', 0, 0), 
                    (7, 1, 'mostsmileys', 0, 0), 
                    (8, 1, 'top5photo', 1, 0), 
                    (9, 1, 'top5photo', 2, 0), 
                    (10, 1, 'top5photo', 3, 0), 
                    (11, 1, 'top5photo', 4, 0), 
                    (12, 1, 'top5photo', 5, 0), 
                    (13, 1, 'topviewedphoto', 0, 0)";
        mysql_query($sql) or die(mysql_error());
        // create fcms_alerts
        $sql = "CREATE TABLE `fcms_alerts` (
                    `id` INT(25) NOT NULL AUTO_INCREMENT, 
                    `msg` TEXT NOT NULL, PRIMARY KEY (`id`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        $sql = "CREATE TABLE `fcms_alerts_users` (
                    `id` INT(25) NOT NULL AUTO_INCREMENT, 
                    `alert` VARCHAR(50) NOT NULL DEFAULT '0', 
                    `user` INT(25) NOT NULL DEFAULT '0', 
                    `show` TINYINT(1) NOT NULL DEFAULT '1', 
                    PRIMARY KEY (`id`), 
                    KEY `alert_ind` (`alert`), 
                    KEY `user_ind` (`user`) 
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
		echo "<div id=\"install\"><h1>".$LANG['install_success']."</h1><p>Family Connections ".$LANG['install_done1']."</p><p>".$LANG['install_done2']." <a href=\"index.php\">".$LANG['install_done3']."</a> ".$LANG['install_done4']." Family Connections.<p></div>";
	}
} ?>

 	  	 
