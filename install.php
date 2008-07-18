<!--
Family Connections - a family oriented CMS -- http://www.haudenschilt.com/fcms/

Copyright (C) 2007 Ryan Haudenschilt

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
<link rel="stylesheet" type="text/css" href="themes/datechooser.css" />
<script src="inc/prototype.js" type="text/javascript"></script>
<script type="text/javascript" src="inc/datechooser.js"></script>
<script type="text/javascript">
<!-- //
	window.onload = WindowLoad;
	function WindowLoad()
	{
		var objDatePicker = new DateChooser();
		objDatePicker.setUpdateField({'day':'j', 'month':'n', 'year':'Y'});
		objDatePicker.setIcon('themes/images/default/datepicker.jpg', 'year');
		return true;
	}
// -->
</script>
<style type="text/css">
html { font-size: 100%; background: #9ccef0 url(themes/images/default/bg.png) repeat-x; }
body { font-size: 12pt; line-height: 24pt; text-align: center; font-family: Verdana, Sans-Serif; }
a { color: #02876c; font-weight: bold; text-decoration: none; }
a:hover { color: #000; background-color: #9ccef0; }
p { font-size: 10pt; line-height: 14pt; }
#column { width: 600px; margin: 0 auto 50px auto; padding: 10px; text-align: left; background-color: #fff }
h1 { color: #fff; margin-top: 150px; }
h2 { color: #fff; font-weight: bold; background-color: #000; margin: 0; padding: 15px 0 11px 15px; }
#sections-photo, #sections-board, #sections-book, #sections-calendar, #sections-news, #sections-prayers { border: none; }
.field-label { margin: 10px 0 0 0 0; }
.field-widget { margin: 10px 0 0 0; }
.error { font-size: 10pt; line-height: 14pt; color: #f30; }
.info { padding: 10px; background-color: #eee; }
.req { font-size: 8pt; color: #c00; }
.LV_valid { font-size: 9pt; padding-left: 10px; font-weight: bold; color: #0c0; }
.LV_valid_field, input.LV_valid_field:hover, input.LV_valid_field:active, textarea.LV_valid_field:hover, textarea.LV_valid_field:active { border: 1px solid #0c0; }
.LV_invalid { display: block; font-size: 8pt; font-weight: bold; color : #c00; }
.LV_invalid_field, input.LV_invalid_field:hover, input.LV_invalid_field:active, textarea.LV_invalid_field:hover, textarea.LV_invalid_field:active { border: 1px solid #c00; }
#submit { font-size: 14pt; line-height: 24pt; font-family: Verdana, Sans-Serif; border: none; }
#install { margin: 50px 0 0 0; width: 60%; }
#install p { font-size: 14pt; line-height: 24pt; }
#install div { float: left; width:48%; padding:10px; font-size: 8pt; }
#install .nbtn { color: #000; padding:10px 25px; background-color: #fff; border: 1px solid #000; }
#install .ybtn { color: #000; padding:10px 20px; background-color: #9ccef0; border: 1px solid #000; }
.progress { border: 1px solid #000; width: 575px; margin: 2px 5px 50px 0; padding: 1px; background: #fff; }
.progress div { background-color: #ace97c; height: 12px; }
</style>
</head>
<body>
<?php
if (!isset($_POST['submit1']) && !isset($_POST['submit2']) && !isset($_POST['submit3']) && !isset($_POST['submit4']) && !isset($_POST['submit5']) && file_exists('inc/config_inc.php')) {
	echo "<div id=\"install\"><h1>".$LANG['already_install1']."</h1><p>".$LANG['already_install2']."</p><div>";
	echo "<a class=\"nbtn\" href=\"index.php\">".$LANG['already_install3']."</a><br/>".$LANG['already_install4']."</div><div>";
	echo "<a class=\"ybtn\" href=\"#\" onclick=\"$('show-install').toggle(); $('install').toggle(); document.setupform.dbhost.focus(); return false\">".$LANG['already_install5']."</a><br/>".$LANG['already_install6']."</div></div>";
	echo "<div id=\"show-install\" style=\"display:none;\">";
}
if (isset($_POST['submit1'])) {
	displayStepTwo();
} else if (isset($_POST['submit2'])) {
	if (!isset($_POST['dbhost']) || !isset($_POST['dbname']) || !isset($_POST['dbuser']) || !isset($_POST['dbpass'])) {
		displayStepTwo("<p class=\"error\">".$LANG['err_required']."</p>");
	} else {
		$file = fopen('inc/config_inc.php', 'w') or die("<h1>Error Creating Config File</h1>");
		$str = "<?php \$cfg_mysql_host = '".$_POST['dbhost']."'; \$cfg_mysql_db = '".$_POST['dbname']."'; \$cfg_mysql_user = '".$_POST['dbuser']."'; \$cfg_mysql_pass = '".$_POST['dbpass']."'; ?".">";
		fwrite($file, $str);
		fclose($file);
		displayStepThree();
	}
} else if (isset($_POST['submit3'])) {
	displayStepFour();
} else if (isset($_POST['submit4'])) {
	if (!isset($_POST['sitename']) || !isset($_POST['contact'])) {
		displayStepFour("<p class=\"error\">".$LANG['err_required']."</p>");
	} else {
		include_once('inc/config_inc.php');
		mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
		mysql_select_db($cfg_mysql_db);
		mysql_query("TRUNCATE TABLE `fcms_config`") or die(mysql_error());
		$sql = "INSERT INTO `fcms_config` (`sitename`, `contact`, `nav_top1`, `nav_top2`, `current_version`) VALUES ('".addslashes($_POST['sitename'])."', '".addslashes($_POST['contact'])."', ";
		if(isset($_POST['sections-news'])) {
			if(isset($_POST['sections-prayers'])) { $sql .= "'familynews', 'prayers', "; } else { $sql .= "'familynews', 'none', "; }
		} else { 
			if(isset($_POST['sections-prayers'])) { $sql .= "'none', 'prayers', "; } else { $sql .= "'none', 'none', "; }
		}
		$sql .= "'Family Connections 1.5')";
		mysql_query($sql) or die(mysql_error());
		displayStepFive();
	}
} else if (isset($_POST['submit5'])) {
	if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['fname']) || !isset($_POST['lname']) || !isset($_POST['email'])) {
		displayStepFive("<p class=\"error\">".$LANG['err_required']."</p>");
	} else {
		setupDatabase($_POST['fname'], $_POST['lname'], $_POST['email'], $birthday, $_POST['username'], $_POST['password'], $_POST['address'], $_POST['city'], $_POST['state'], $_POST['zip'], $_POST['home'], $_POST['work'], $_POST['cell']);
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
	<h1><?php echo $LANG['install']; ?> Family Connections</h1>
	<script type="text/javascript" src="inc/prototype.js"></script>
	<script type="text/javascript" src="inc/livevalidation.js"></script>
	<form action="install.php" method="post">
	<div id="column">
		<h2>Pre-Installation Check</h2>
		<div style="text-align:center">Step 1 of 5</div><div class="progress"><div style="width:20%"></div></div>
		<div><b>Checking Folder Permissions</b></div>
		<div><div style="width:250px;float:left;">inc/</div> <div style="width:250px;float:left;"><?php if (is__writable('inc/')) { echo "<span style=\"color:#0c0\">OK</span>"; $inc = true; } else { echo "<span style=\"color:#c00\">BAD</span>"; } ?></div></div>
		<div style="clear:both;"></div>
		<div><div style="width:250px;float:left;">gallery/avatar/</div> <div style="width:250px;float:left;"><?php if (is__writable('gallery/avatar/')) { echo "<span style=\"color:#0c0\">OK</span>"; $avatar = true; } else { echo "<span style=\"color:#c00\">BAD</span>"; } ?></div></div>
		<div style="clear:both;"></div>
		<div><div style="width:250px;float:left;">gallery/photos/</div> <div style="width:250px;float:left;"><?php if (is__writable('gallery/photos/')) { echo "<span style=\"color:#0c0\">OK</span>"; $photos = true; } else { echo "<span style=\"color:#c00\">BAD</span>"; } ?></div></div>
		<div style="clear:both;"></div>
		<div><div style="width:250px;float:left;">gallery/upimages/</div> <div style="width:250px;float:left;"><?php if (is__writable('gallery/upimages/')) { echo "<span style=\"color:#0c0\">OK</span>"; $up = true; } else { echo "<span style=\"color:#c00\">BAD</span>"; } ?></div></div>
		<div style="clear:both;"></div><?php 
		if ($inc && $avatar && $photos && $up) { ?>
		<div>Your site is ready to be installed.  Please proceed to the next step.</div>
		<p style="text-align:right;"><input id="submit" name="submit1" type="submit"  value="<?php echo $LANG['next']; ?> >>"/></p>
		<?php } else { ?>
		<div>Unfortunatly your site is not ready to be installed.  Please make sure that the folders above exist and have the <a href="http://www.haudenschilt.com/fcms/support/faq.html#chmod">proper permissions set</a>.</div>
		<?php } ?>
	</div>
	</form><?php
}
function displayStepTwo ($error = '0') {
	global $LANG; ?>
	<h1><?php echo $LANG['install']; ?> Family Connections</h1>
	<script type="text/javascript" src="inc/prototype.js"></script>
	<script type="text/javascript" src="inc/livevalidation.js"></script>
	<form action="install.php" method="post">
	<div id="column">
		<?php if ($error !== '0') { echo $error; } ?>
		<h2><?php echo $LANG['db_info']; ?></h2>
		<div style="text-align:center">Step 2 of 5</div><div class="progress"><div style="width:40%"></div></div>
		<div><div class="field-label"><label for="dbhost"><b><?php echo $LANG['db_host']; ?></b></label>: (<span class="req">*</span>)</div> <div class="field-widget"><input type="text" name="dbhost" size="50" id="dbhost" class="required" value="" title="<?php echo $LANG['title_db_host']; ?>"/></div></div>
		<script type="text/javascript">
			var fdbhost = new LiveValidation('dbhost', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500});
			fdbhost.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_ins_sorry_req']; ?>"});
		</script> 	
		<p><?php echo $LANG['db_host_desc1']." <i>".$LANG['db_host_desc2']."</i> ".$LANG['db_host_desc3']?></p>
		<div><div class="field-label"><label for="dbname"><b><?php echo $LANG['db_name']; ?></b></label>: (<span class="req">*</span>)</div> <div class="field-widget"><input type="text" name="dbname" size="50" id="dbname" class="required" value="" title="<?php echo $LANG['title_db_name']; ?>"/></div></div>
		<script type="text/javascript">
			var fdbname = new LiveValidation('dbname', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500});
			fdbname.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_ins_sorry_req']; ?>"});
		</script> 	
		<p><?php echo $LANG['db_name_desc']; ?> Family Connections.</p>
		<div><div class="field-label"><label for="dbuser"><b><?php echo $LANG['db_uname']; ?></b></label>: (<span class="req">*</span>)</div> <div class="field-widget"><input type="text" name="dbuser" size="50" id="dbuser" class="required" value="" title="<?php echo $LANG['title_db_uname']; ?>"/></div></div>
		<script type="text/javascript">
			var fdbuser = new LiveValidation('dbuser', { validMessage: "<?php echo $LANG['lv_good_dbuser']; ?>", wait: 500});
			fdbuser.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_bad_dbuser']; ?>"});
		</script> 	
		<p><?php echo $LANG['db_uname_desc']; ?></p>
		<div><div class="field-label"><label for="dbpass"><b><?php echo $LANG['db_pass']; ?></b></label>: (<span class="req">*</span>)</div>	<div class="field-widget"><input type="password" name="dbpass" size="50" id="dbpass" class="required" value="" title="<?php echo $LANG['title_db_pass']; ?>"/></div></div>
		<script type="text/javascript">
			var fdbpass = new LiveValidation('dbpass', { validMessage: "<?php echo $LANG['lv_good_pass']; ?>", wait: 500});
			fdbpass.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_bad_pass']; ?>"});
		</script>
		<p><?php echo $LANG['db_pass_desc']; ?></p>
		<p style="text-align:right;"><input id="submit" name="submit2" type="submit"  value="<?php echo $LANG['next']; ?> >>"/></p>
	</div>
	</form><?php
}
function displayStepThree () {
	include_once('inc/config_inc.php');
	global $LANG; ?>
	<h1><?php echo $LANG['install']; ?> Family Connections</h1>
	<script type="text/javascript" src="inc/prototype.js"></script>
	<script type="text/javascript" src="inc/livevalidation.js"></script>
	<form action="install.php" method="post">
	<div id="column">
		<h2>Checking Database Connection</h2>
		<div style="text-align:center">Step 3 of 5</div><div class="progress"><div style="width:60%"></div></div>
		<div>Attempting to connect to database <i><?php echo $cfg_mysql_db; ?></i> on <i><?php echo $cfg_mysql_host; ?></i> using user <i><?php echo $cfg_mysql_user; ?></i>...</div>
		<?php
		$connection = @mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
		if (!$connection) {
			die("<h3 style=\"color:#c00\">Uh-Oh!</h3><div>A connection to the database could not be made.  Please shut down your browser and then re-run the installation.</div>");
		} else {
			mysql_select_db($cfg_mysql_db) or die("<h1>Error</h1><p><b>Connection made, but database could not be found!</b></p>" . mysql_error());
			echo "<h3 style=\"color:#0c0\">Awesome!</h3><div>A connection was successfully made to the database.  Please proceed to the next step.</div>";
			mysql_query("DROP TABLE IF EXISTS `fcms_config`") or die("<h1>Error</h1><p><b>Could not drop `fcms_config` table.</b></p>" . mysql_error());
			mysql_query("CREATE TABLE `fcms_config` (`sitename` varchar(50) NOT NULL DEFAULT 'My Site', `contact` varchar(50) NOT NULL DEFAULT 'nobody@yoursite.com', `nav_top1` set('familynews','prayers','none') NOT NULL default 'familynews', `nav_top2` set('familynews','prayers','none') NOT NULL default 'prayers', `current_version` varchar(50) NOT NULL DEFAULT 'Family Connections') ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
		} ?>
		<p style="text-align:right;"><input id="submit" name="submit3" type="submit"  value="<?php echo $LANG['next']; ?> >>"/></p>
	</div>
	</form><?php
}
function displayStepFour ($error = '0') {
	global $LANG; ?>
	<h1><?php echo $LANG['install']; ?> Family Connections</h1>
	<script type="text/javascript" src="inc/prototype.js"></script>
	<script type="text/javascript" src="inc/livevalidation.js"></script>
	<form action="install.php" method="post">
	<div id="column">
		<h2><?php echo $LANG['site_info']; ?></h2>
		<div style="text-align:center">Step 4 of 5</div><div class="progress"><div style="width:80%"></div></div>
		<div><div class="field-label"><label for="sitename"><b><?php echo $LANG['site_name'];?></b></label>: (<span class="req">*</span>)</div>	<div class="field-widget"><input type="text" name="sitename" size="50" id="sitename" class="required" value="" title="<?php echo $LANG['title_site_name']; ?>"/></div></div>
		<script type="text/javascript">
			var fsitename = new LiveValidation('sitename', { validMessage: "<?php echo $LANG['lv_good_sitename']; ?>", wait: 500});
			fsitename.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_bad_sitename']; ?>"});
		</script>
		<p><?php echo $LANG['site_name_desc']; ?></p>
		<div><div class="field-label"><label for="contact"><b><?php echo $LANG['contact']; ?></b></label>: (<span class="req">*</span>)</div> <div class="field-widget"><input type="text" name="contact" size="50" id="contact" class="required validate-email" value="" title="<?php echo $LANG['title_contact']; ?>"/></div></div>
		<script type="text/javascript">
			var fcontact = new LiveValidation('contact', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500 });
			fcontact.add( Validate.Presence, { failureMessage: "<?php echo $LANG['lv_ins_sorry_req']; ?>" } );
			fcontact.add( Validate.Email, { failureMessage: "<?php echo $LANG['lv_bad_email']; ?>" } );
			fcontact.add( Validate.Length, { minimum: 10 } );
		</script>
		<p><?php echo $LANG['contact_desc']; ?></p>
		<div><div class="field-label"><label for="sections-photo"><b><?php echo $LANG['sections']; ?></b></label>:</div> <div class="field-widget">
			<input type="checkbox" checked="checked" disabled="disabled" name="sections-photo" id="sections-photo" value="" /><?php echo $LANG['link_gallery']; ?> <span class="error"><span class="error">(<?php echo $LANG['required']; ?>)</span></span><br />
			<input type="checkbox" checked="checked" disabled="disabled" name="sections-board" id="sections-board" value="" /><?php echo $LANG['link_board']; ?> <span class="error">(<?php echo $LANG['required']; ?>)</span><br />
			<input type="checkbox" checked="checked" disabled="disabled" name="sections-book" id="sections-book" value="" /><?php echo $LANG['link_address']; ?> <span class="error">(<?php echo $LANG['required']; ?>)</span><br />
			<input type="checkbox" checked="checked" disabled="disabled" name="sections-calendar" id="sections-calendar" value="" /><?php echo $LANG['link_calendar']; ?> <span class="error">(<?php echo $LANG['required']; ?>)</span><br />
			<input type="checkbox" name="sections-news" id="sections-news" value="familynews" /><?php echo $LANG['link_news']; ?><br/>
			<input type="checkbox" name="sections-prayers" id="sections-prayers" value="prayerconcerns" /><?php echo $LANG['link_prayer']; ?>
		</div></div>
		<p><?php echo $LANG['sections_desc']; ?></p>
		<p style="text-align:right;"><input id="submit" name="submit4" type="submit"  value="<?php echo $LANG['next']; ?> >>"/></p>
	</div>
	</form><?php
}
function displayStepFive ($error = '0') {
	global $LANG; ?>
	<h1><?php echo $LANG['install']; ?> Family Connections</h1>
	<script type="text/javascript" src="inc/prototype.js"></script>
	<script type="text/javascript" src="inc/livevalidation.js"></script>
	<form action="install.php" method="post">
	<div id="column">
		<h2><?php echo $LANG['admin_account']; ?></h2>
		<div style="text-align:center">Step 5 of 5</div><div class="progress"><div style="width:100%"></div></div>
		<p><?php echo $LANG['admin_desc1']; ?></p>
		<p><?php echo $LANG['admin_desc2']; ?></p>
		<div><div class="field-label"><label for="username"><b><?php echo $LANG['username']; ?></b></label>: (<span class="req">*</span>)</div> <div class="field-widget"><input type="text" name="username" id="username" class="required" title="<?php echo $LANG['title_uname']; ?>" size="25" value=""/></div></div>
		<script type="text/javascript">
			var funame = new LiveValidation('username', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500});
			funame.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_ins_sorry_req']; ?>"});
		</script>
		<div><div class="field-label"><label for="password"><b><?php echo $LANG['password']; ?></b></label>: (<span class="req">*</span>)</div> <div class="field-widget"><input type="password" name="password" id="password" class="required" title="<?php echo $LANG['title_pass']; ?>" size="25" value=""/></div></div>
		<script type="text/javascript">
			var fpass = new LiveValidation('password', { validMessage: "<?php echo $LANG['lv_good_pass']; ?>", wait: 500});
			fpass.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_bad_pass']; ?>"});
		</script>
		<div><div class="field-label"><label for="fname"><b><?php echo $LANG['first_name']; ?></b></label>: (<span class="req">*</span>)</div> <div class="field-widget"><input type="text" name="fname" size="50" id="fname" class="required" value="" title="<?php echo $LANG['title_fname']; ?>"/></div></div>
		<script type="text/javascript">
			var ffname = new LiveValidation('fname', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500});
			ffname.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_ins_sorry_req']; ?>"});
		</script>
		<div><div class="field-label"><label for="lname"><b><?php echo $LANG['last_name']; ?></b></label>: (<span class="req">*</span>)</div> <div class="field-widget"><input type="text" name="lname" size="50" id="lname" class="required" value="" title="<?php echo $LANG['title_lname']; ?>"/></div></div>
		<script type="text/javascript">
			var flname = new LiveValidation('lname', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500});
			flname.add(Validate.Presence, {failureMessage: "<?php echo $LANG['lv_ins_sorry_req']; ?>"});
		</script>
		<div><div class="field-label"><label for="email"><b><?php echo $LANG['email_address']; ?></b></label>: (<span class="req">*</span>)</div> <div class="field-widget"><input type="text" name="email" size="50" id="email" class="required validate-email" value="" title="<?php echo $LANG['title_email']; ?>"/></div></div>
		<script type="text/javascript">
			var femail = new LiveValidation('email', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500 });
			femail.add( Validate.Presence, { failureMessage: "<?php echo $LANG['lv_ins_sorry_req']; ?>" } );
			femail.add( Validate.Email, { failureMessage: "<?php echo $LANG['lv_bad_email']; ?>" } );
			femail.add( Validate.Length, { minimum: 10 } );
		</script>
		<div><div class="field-label"><label for="day"><b><?php echo $LANG['birthday']; ?></b></label>: (<span class="req">*</span>)</div> <div class="field-widget"><select id="day" name="day">
			<?php
			$d = 1;
			while ($d <= 31) {
				if ($day == $d) { echo "<option value=\"$d\" selected=\"selected\">$d</option>"; }
				else { echo "<option value=\"$d\">$d</option>"; }
				$d++;
			}
			echo '</select><select name="month">';
			$m = 1;
			while ($m <= 12) {
				$lang_month = "".date('M', mktime(0, 0, 0, $m, 1, 2006));
				if ($month == $m) { echo "<option value=\"$m\" selected=\"selected\">" . $LANG[$lang_month] . "</option>"; }
				else { echo "<option value=\"$m\">" . $LANG[$lang_month] . "</option>"; }
				$m++;
			}
			echo '</select><select name="year">';
			$y = 1900;
			while ($y - 5 <= date('Y')) {
				if ($year == $y) { echo "<option value=\"$y\" selected=\"selected\">$y</option>"; }
				else { echo "<option value=\"$y\">$y</option>"; }
				$y++;
			} ?></select></div></div>
		<div><div class="field-label"><label for="address"><b><?php echo $LANG['street']; ?></b></label>:</div> <div class="field-widget"><input type="text" name="address" size="50" id="address" class="" value="" title="<?php echo $LANG['title_street']; ?>"/></div></div>
		<script type="text/javascript">
			var faddress = new LiveValidation('address', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500});
		</script>
		<div><div class="field-label"><label for="city"><b><?php echo $LANG['city_town']; ?></b></label>:</div> <div class="field-widget"><input type="text" name="city" size="50" id="city" class="" value="" title="<?php echo $LANG['title_city_town']; ?>"/></div></div>
		<script type="text/javascript">
			var fcity = new LiveValidation('city', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500});
		</script>
		<div><div class="field-label"><label for="state"><b><?php echo $LANG['state_prov']; ?></b></label>:</div> <div class="field-widget"><input type="text" name="state" id="state" class="" title="<?php echo $LANG['title_state_prov']; ?>" size="50" value=""/></div></div>
		<script type="text/javascript">
			var fstate = new LiveValidation('state', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500});
		</script>
		<div><div class="field-label"><label for="zip"><b><?php echo $LANG['zip_pos']; ?></b></label>:</div> <div class="field-widget"><input type="text" name="zip" id="zip" class="" title="<?php echo $LANG['title_zip_pos']; ?>" size="10" value=""/></div></div>
		<script type="text/javascript">
			var fzip = new LiveValidation('zip', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500});
		</script>
		<div><div class="field-label"><label for="home"><b><?php echo $LANG['home_phone']; ?></b></label>:</div> <div class="field-widget"><input type="text" name="home" id="home" class="validate-phone" title="<?php echo $LANG['title_phone']; ?>" size="20" value=""/></div></div>
		<script type="text/javascript">
			var fhome = new LiveValidation('home', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500});
			fhome.add( Validate.Format, { pattern: /^[0-9\.\-\x\s\+\(\)]+$/ } );
		</script>
		<div><div class="field-label"><label for="work"><b><?php echo $LANG['work_phone']; ?></b></label>:</div> <div class="field-widget"><input type="text" name="work" id="work" class="validate-phone" title="<?php echo $LANG['title_phone']; ?>" size="20" value=""/></div></div>
		<script type="text/javascript">
			var fwork = new LiveValidation('work', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500});
			fwork.add( Validate.Format, { pattern: /^[0-9\.\-\x\s\+\(\)]+$/ } );
		</script>
		<div><div class="field-label"><label for="cell"><b><?php echo $LANG['mobile_phone']; ?></b></label>:</div> <div class="field-widget"><input type="text" name="cell" id="cell" class="validate-phone" title="<?php echo $LANG['title_phone']; ?>" size="20" value=""/></div></div>
		<script type="text/javascript">
			var fcell = new LiveValidation('cell', { validMessage: "<?php echo $LANG['lv_thanks']; ?>", wait: 500});
			fcell.add( Validate.Format, { pattern: /^[0-9\.\-\x\s\+\(\)]+$/ } );
		</script>
		<p style="text-align:right;"><input id="submit" name="submit5" type="submit"  value="<?php echo $LANG['next']; ?> >>"/></p>
	</div>
	</form><?php
}
function setupDatabase ($fname, $lname, $email, $birthday, $username, $password, $address='false', $city='false', $state='false', $zip='false', $home='false', $work='false', $cell='false') {
	include_once('inc/config_inc.php');
	include_once('inc/util_inc.php');
	global $LANG;
	$password = md5($password);
	$connection = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
	if (!$connection) {
		die("<h1>Connection Error (install.php 194)</h1>" . mysql_error());
	} else {
		mysql_select_db($cfg_mysql_db) or die("<h1>Error</h1><p><b>Database could not be found!</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_address`") or die("<h1>Error</h1><p><b>Could not drop `fcms_address` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_calendar`") or die("<h1>Error</h1><p><b>Could not drop `fcms_calendar` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_gallery_comments`") or die("<h1>Error</h1><p><b>Could not drop `fcms_gallery_comments` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_gallery_photos`") or die("<h1>Error</h1><p><b>Could not drop `fcms_gallery_photos` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_gallery_category`") or die("<h1>Error</h1><p><b>Could not drop `fcms_gallery_category` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_news_comments`") or die("<h1>Error</h1><p><b>Could not drop `fcms_news_comments` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_news`") or die("<h1>Error</h1><p><b>Could not drop `fcms_news` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_poll_users`") or die("<h1>Error</h1><p><b>Could not drop `fcms_poll_users` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_poll_options`") or die("<h1>Error</h1><p><b>Could not drop `fcms_poll_options` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_polls`") or die("<h1>Error</h1><p><b>Could not drop `fcms_polls` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_prayers`") or die("<h1>Error</h1><p><b>Could not drop `fcms_prayers` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_board_posts`") or die("<h1>Error</h1><p><b>Could not drop `fcms_board_posts` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_board_threads`") or die("<h1>Error</h1><p><b>Could not drop `fcms_board_threads` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_user_awards`") or die("<h1>Error</h1><p><b>Could not drop `fcms_user_awards` table.</b></p>" . mysql_error());
		mysql_query("DROP TABLE IF EXISTS `fcms_users`") or die("<h1>Error</h1><p><b>Could not drop `fcms_users` table.</b></p>" . mysql_error());
		mysql_query("SET NAMES utf8") or die("<h1>Error</h1><p><b>Could not set encoding</b></p>" . mysql_error());
		mysql_query("CREATE TABLE `fcms_users` (`id` int(25) NOT NULL auto_increment, `access` tinyint(1) NOT NULL default '3', `activity` datetime NOT NULL default '0000-00-00 00:00:00', `joindate` timestamp NOT NULL default CURRENT_TIMESTAMP, `fname` varchar(25) NOT NULL default 'fname', `lname` varchar(25) NOT NULL default 'lname', `email` varchar(50) NOT NULL default 'me@mail.com', `birthday` date NOT NULL default '0000-00-00', `theme` varchar(25) NOT NULL default 'default.css', `username` varchar(25) NOT NULL default '0', `password` varchar(255) NOT NULL default '0', `avatar` varchar(25) NOT NULL default '0x0.gif', `boardsort` set('ASC','DESC') NOT NULL default 'ASC', `showavatar` set('YES','NO') NOT NULL default 'YES', `displayname` set('1','2','3') NOT NULL default '1', `frontpage` set('1','2') NOT NULL default '1', `timezone` set('-12 hours','-11 hours','-10 hours','-9 hours','-8 hours','-7 hours','-6 hours','-5 hours','-4 hours','-3 hours -30 minutes','-3 hours','-2 hours','-1 hours','-0 hours','+1 hours','+2 hours','+3 hours','+3 hours +30 minutes','+4 hours','+4 hours +30 minutes','+5 hours','+5 hours +30 minutes','+6 hours','+7 hours','+8 hours','+9 hours','+9 hours +30 minutes','+10 hours','+11 hours','+12 hours') NOT NULL default '-5 hours', `dst` tinyint(1) NOT NULL default '0', `activated` tinyint(1) NOT NULL default '0', PRIMARY KEY  (`id`), UNIQUE KEY `username` (`username`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_users` (`id`, `access`, `joindate`, `fname`, `lname`, `email`, `birthday`, `username`, `password`, `activated`) VALUES (1, 1, NOW(), '".addslashes($fname)."', '".addslashes($lname)."', '".addslashes($email)."', '$birthday', '".addslashes($username)."', '$password', 1)") or die(mysql_error());
		mysql_query("CREATE TABLE `fcms_address` (`id` int(11) NOT NULL auto_increment, `user` int(11) NOT NULL default '0', `entered_by` INT(11) NOT NULL DEFAULT '0', `updated` timestamp NOT NULL default CURRENT_TIMESTAMP, `address` varchar(50) default NULL, `city` varchar(50) default NULL, `state` varchar(50) default NULL, `zip` varchar(10) default NULL, `home` varchar(20) default NULL, `work` varchar(20) default NULL, `cell` varchar(20) default NULL, PRIMARY KEY  (`id`), KEY `user_ind` (`user`), KEY `ent_ind` (`entered_by`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
		mysql_query("ALTER TABLE `fcms_address` ADD CONSTRAINT `fcms_address_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_address` (`id`, `user`, `address`, `city`, `state`, `zip`, `home`, `work`, `cell`) VALUES (NULL, 1, '".addslashes($address)."', '".addslashes($city)."', '".addslashes($state)."', '".addslashes($zip)."', '".addslashes($home)."', '".addslashes($work)."', '".addslashes($cell)."')") or die(mysql_error());
		mysql_query("CREATE TABLE `fcms_calendar` (`id` int(11) NOT NULL auto_increment, `date` date NOT NULL default '0000-00-00', `title` varchar(50) NOT NULL default 'MyDate', `desc` text, `created_by` int(11) NOT NULL default '0', `type` set('Birthday','Anniversary','Holiday','Other') NOT NULL default 'Other', `private` TINYINT(1) NOT NULL DEFAULT '0', PRIMARY KEY  (`id`), KEY `by_ind` (`created_by`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
		mysql_query("ALTER TABLE `fcms_calendar` ADD CONSTRAINT `fcms_calendar_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_calendar` (`id`, `date`, `title`, `desc`, `created_by`, `type`) VALUES (NULL, '$birthday', '".addslashes($fname)." ".addslashes($lname)."', NULL, 1, 'Birthday'), (NULL, '2007-12-25', 'Christmas', NULL, 1, 'Holiday'), (NULL, '2007-02-14', 'Valentine''s Day', NULL, 1, 'Holiday'), (NULL, '2007-01-01', 'New Year''s Day', NULL, 1, 'Holiday'), (NULL, '2007-07-04', 'Independence Day', NULL, 1, 'Holiday'), (NULL, '2007-02-02', 'Groundhog Day', NULL, 1, 'Holiday'), (NULL, '2007-03-17', 'St. Patrick''s Day', NULL, 1, 'Holiday'), (NULL, '2007-04-01', 'April Fools Day', NULL, 1, 'Holiday'), (NULL, '2007-10-31', 'Halloween', NULL, 1, 'Holiday')") or die(mysql_error());
		mysql_query("CREATE TABLE `fcms_gallery_category` (`id` int(11) NOT NULL auto_increment, `name` varchar(50) NOT NULL default 'category', `user` int(11) NOT NULL default '0', PRIMARY KEY  (`id`), KEY `user_ind` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
		mysql_query("ALTER TABLE `fcms_gallery_category` ADD CONSTRAINT `fcms_gallery_category_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE") or die(mysql_error());
		mysql_query("CREATE TABLE `fcms_gallery_photos` (`id` int(11) NOT NULL auto_increment, `date` timestamp NOT NULL default '0000-00-00 00:00:00', `filename` varchar(25) NOT NULL default 'noimage.gif', `caption` text, `category` int(11) NOT NULL default '0', `user` int(11) NOT NULL default '0', `views` smallint(6) NOT NULL default '0', `votes` smallint(6) NOT NULL default '0', `rating` float NOT NULL default '0', PRIMARY KEY  (`id`), KEY `cat_ind` (`category`), KEY `user_ind` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
		mysql_query("ALTER TABLE `fcms_gallery_photos` ADD CONSTRAINT `fcms_gallery_photos_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `fcms_gallery_photos_ibfk_2` FOREIGN KEY (`category`) REFERENCES `fcms_gallery_category` (`id`) ON DELETE CASCADE") or die(mysql_error());
		mysql_query("CREATE TABLE `fcms_gallery_comments` (`id` int(11) NOT NULL auto_increment, `photo` int(11) NOT NULL default '0', `comment` text NOT NULL, `date` timestamp NOT NULL default '0000-00-00 00:00:00', `user` int(11) NOT NULL default '0', PRIMARY KEY  (`id`), KEY `photo_ind` (`photo`), KEY `user_ind` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
		mysql_query("ALTER TABLE `fcms_gallery_comments` ADD CONSTRAINT `fcms_gallery_comments_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `fcms_gallery_comments_ibfk_2` FOREIGN KEY (`photo`) REFERENCES `fcms_gallery_photos` (`id`) ON DELETE CASCADE") or die(mysql_error());
		if (usingFamilyNews()) {
			mysql_query("CREATE TABLE `fcms_news` (`id` int(11) NOT NULL auto_increment, `title` varchar(50) NOT NULL default '', `news` text NOT NULL, `user` int(11) NOT NULL default '0', `date` datetime NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY  (`id`), KEY `userindx` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
			mysql_query("ALTER TABLE `fcms_news` ADD CONSTRAINT `fcms_news_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE") or die(mysql_error());
			mysql_query("CREATE TABLE `fcms_news_comments` (`id` int(11) NOT NULL auto_increment, `news` int(11) NOT NULL default '0', `comment` text NOT NULL, `date` timestamp NOT NULL default '0000-00-00 00:00:00', `user` int(11) NOT NULL default '0', PRIMARY KEY  (`id`), KEY `photo_ind` (`news`), KEY `user_ind` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
			mysql_query("ALTER TABLE `fcms_news_comments` ADD CONSTRAINT `fcms_news_comments_ibfk_2` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `fcms_news_comments_ibfk_1` FOREIGN KEY (`news`) REFERENCES `fcms_news` (`id`) ON DELETE CASCADE") or die(mysql_error());
		}
		mysql_query("CREATE TABLE `fcms_polls` (`id` int(11) NOT NULL auto_increment, `question` text NOT NULL, `started` datetime NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY  (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_polls` (`id`, `question`, `started`) VALUES (NULL, 'Family Connections software is...', NOW())") or die(mysql_error());
		mysql_query("CREATE TABLE `fcms_poll_options` (`id` int(11) NOT NULL auto_increment, `poll_id` int(11) NOT NULL default '0', `option` text NOT NULL, `votes` int(11) NOT NULL default '0', PRIMARY KEY  (`id`), KEY `pollid_ind` (`poll_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
		mysql_query("ALTER TABLE `fcms_poll_options` ADD CONSTRAINT `fcms_poll_options_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `fcms_polls` (`id`) ON DELETE CASCADE") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_poll_options` (`id`, `poll_id`, `option`, `votes`) VALUES (NULL, 1, 'Easy to use!', 0), (NULL, 1, 'Visually appealing!', 0), (NULL, 1, 'Just what our family needed!', 0)") or die(mysql_error());
		mysql_query("CREATE TABLE `fcms_poll_users` (`id` int(11) NOT NULL auto_increment, `user` int(11) NOT NULL default '0', `option` int(11) NOT NULL default '0', PRIMARY KEY  (`id`), KEY `user_ind` (`user`), KEY `option_ind` (`option`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
		mysql_query("ALTER TABLE `fcms_poll_users` ADD CONSTRAINT `fcms_poll_users_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `fcms_poll_users_ibfk_2` FOREIGN KEY (`option`) REFERENCES `fcms_poll_options` (`id`) ON DELETE CASCADE") or die(mysql_error());
		if (usingPrayers()) {
			mysql_query("CREATE TABLE `fcms_prayers` (`id` int(11) NOT NULL auto_increment, `for` varchar(50) NOT NULL default '', `desc` text NOT NULL, `user` int(11) NOT NULL default '0', `date` datetime NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY  (`id`), KEY `userindx` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
			mysql_query("ALTER TABLE `fcms_prayers` ADD CONSTRAINT `fcms_prayers_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE") or die(mysql_error());
		}
		mysql_query("CREATE TABLE `fcms_board_threads` (`id` int(11) NOT NULL auto_increment, `subject` varchar(50) NOT NULL default 'Subject', `started_by` int(11) NOT NULL default '0', `updated` timestamp NOT NULL default '0000-00-00 00:00:00', `updated_by` int(11) NOT NULL default '0', `views` smallint(6) NOT NULL default '0', PRIMARY KEY  (`id`), KEY `start_ind` (`started_by`), KEY `up_ind` (`updated_by`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
		mysql_query("ALTER TABLE `fcms_board_threads` ADD CONSTRAINT `fcms_threads_ibfk_1` FOREIGN KEY (`started_by`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `fcms_threads_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_board_threads` (`id`, `subject`, `started_by`, `updated`, `updated_by`, `views`) VALUES (1, '".$LANG['welcome']."', 1, NOW(), 1, 0)") or die(mysql_error());
		mysql_query("CREATE TABLE `fcms_board_posts` (`id` int(11) NOT NULL auto_increment, `date` timestamp NOT NULL default '0000-00-00 00:00:00', `thread` int(11) NOT NULL default '0', `user` int(11) NOT NULL default '0', `post` text NOT NULL, PRIMARY KEY  (`id`), KEY `thread_ind` (`thread`), KEY `user_ind` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
		mysql_query("ALTER TABLE `fcms_board_posts` ADD CONSTRAINT `fcms_posts_ibfk_1` FOREIGN KEY (`thread`) REFERENCES `fcms_board_threads` (`id`) ON DELETE CASCADE, ADD CONSTRAINT `fcms_posts_ibfk_2` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_board_posts` (`id`, `date`, `thread`, `user`, `post`) VALUES (NULL, NOW(), 1, 1, '".$LANG['welcome_post']."')") or die(mysql_error());
		mysql_query("CREATE TABLE `fcms_user_awards` (`id` int(11) NOT NULL auto_increment, `user` int(11) NOT NULL default '0', `type` varchar(20) NOT NULL default '0', `value` smallint(4) NOT NULL default '0', `count` smallint(4) NOT NULL default '0', PRIMARY KEY  (`id`), KEY `user` (`user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die(mysql_error());
		mysql_query("ALTER TABLE `fcms_user_awards` ADD CONSTRAINT `fcms_user_awards_ibfk_1` FOREIGN KEY (`user`) REFERENCES `fcms_users` (`id`) ON DELETE CASCADE") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_user_awards` (`id`, `user`, `type`, `value`, `count`) VALUES (1, 1, 'top5poster', 1, 0)") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_user_awards` (`id`, `user`, `type`, `value`, `count`) VALUES (2, 1, 'top5poster', 2, 0)") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_user_awards` (`id`, `user`, `type`, `value`, `count`) VALUES (3, 1, 'top5poster', 3, 0)") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_user_awards` (`id`, `user`, `type`, `value`, `count`) VALUES (4, 1, 'top5poster', 4, 0)") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_user_awards` (`id`, `user`, `type`, `value`, `count`) VALUES (5, 1, 'top5poster', 5, 0)") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_user_awards` (`id`, `user`, `type`, `value`, `count`) VALUES (6, 1, 'topthreadstarter', 0, 0)") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_user_awards` (`id`, `user`, `type`, `value`, `count`) VALUES (7, 1, 'mostsmileys', 0, 0)") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_user_awards` (`id`, `user`, `type`, `value`, `count`) VALUES (8, 1, 'top5photo', 1, 0)") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_user_awards` (`id`, `user`, `type`, `value`, `count`) VALUES (9, 1, 'top5photo', 2, 0)") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_user_awards` (`id`, `user`, `type`, `value`, `count`) VALUES (10, 1, 'top5photo', 3, 0)") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_user_awards` (`id`, `user`, `type`, `value`, `count`) VALUES (11, 1, 'top5photo', 4, 0)") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_user_awards` (`id`, `user`, `type`, `value`, `count`) VALUES (12, 1, 'top5photo', 5, 0)") or die(mysql_error());
		mysql_query("INSERT INTO `fcms_user_awards` (`id`, `user`, `type`, `value`, `count`) VALUES (13, 1, 'topviewedphoto', 0, 0)") or die(mysql_error());
		echo "<div id=\"install\"><h1>".$LANG['install_success']."</h1><p>Family Connections ".$LANG['install_done1']."</p><p>".$LANG['install_done2']." <a href=\"index.php\">".$LANG['install_done3']."</a> ".$LANG['install_done4']." Family Connections.<p></div>";
	}
} ?>