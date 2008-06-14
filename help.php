<?php
session_start();
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/language.php');
if (isset($_SESSION['login_id'])) {
	if (!isLoggedIn($_SESSION['login_id'], $_SESSION['login_uname'], $_SESSION['login_pw'])) {
		displayLoginPage();
		exit();
	}
} elseif (isset($_COOKIE['fcms_login_id'])) {
	if (isLoggedIn($_COOKIE['fcms_login_id'], $_COOKIE['fcms_login_uname'], $_COOKIE['fcms_login_pw'])) {
		$_SESSION['login_id'] = $_COOKIE['fcms_login_id'];
		$_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
		$_SESSION['login_pw'] = $_COOKIE['fcms_login_pw'];
	} else {
		displayLoginPage();
		exit();
	}
} else {
	displayLoginPage();
	exit();
}
header("Cache-control: private"); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo $cfg_sitename . " - " . $LANG['poweredby'] . " " . $stgs_release; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="<?php getTheme($_SESSION['login_id']); ?>" />
<link rel="shortcut icon" href="themes/images/favicon.ico"/>
</head>
<body id="body-help">
	<div><a name="top"></a></div>
	<div id="header"><h1 id="logo"><?php echo $cfg_sitename; ?></h1><p>Welcome <a href="profile.php?member=<?php echo $_SESSION['login_id']; ?>"><?php echo getUserDisplayName($_SESSION['login_id']); ?></a> | <a href="settings.php">My Settings</a> | <a href="logout.php" title="Logout">Logout</a></p></div>
	<?php displayTopNav(); ?>
	<div id="pagetitle"><?php echo $LANG['link_help']; ?></div>
	<div id="leftcolumn">
		<h2><?php echo $LANG['navigation']; ?></h2>
		<?php
		displaySideNav();
		if(checkAccess($_SESSION['login_id']) < 3) { 
			echo "\t<h2>".$LANG['admin']."</h2>\n\t"; 
			displayAdminNav("fix");
		} ?></div>
	<div id="content">
		<div class="centercontent">
			<br/>
			<h4>Photo Gallery</h4>
			<p><a href="#gallery-howworks">How does the Photo Gallery work?</a></p>
			<p><a href="#gallery-addphoto">How do I add a photo?</a></p>
			<p><a href="#gallery-chgphoto">How do I edit/change a photo?</a></p>
			<p><a href="#gallery-delphoto">How do I delete a photo?</a></p>
			<p><a href="#gallery-addcat">How do I add a category?</a></p>
			<p><a href="#gallery-chgcat">How do I rename a category?</a></p>
			<p><a href="#gallery-delcat">How do I delete a category?</a></p>
			<p>&nbsp;</p>
			<h4>Personal Settings</h4>
			<p><a href="#settings-avatar">How do I add/change my avatar?</a></p>
			<p><a href="#settings-avatar">How do I change my theme?</a></p>
			<p><a href="#settings-avatar">How do I change my password?</a></p>
			<p>&nbsp;</p>
			<h4>Address Book</h4>
			<p><a href="#address-massemail">How do I email multiple people (Mass Email)?</a></p>
			<p>&nbsp;</p>
			<h4>Administration</h4>
			<p><a href="#adm-access">Member Access Levels</a></p>
			<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
			<p><b><a name="gallery-howworks">How does the Photo Gallery work?</a></b></p>
			<p>
				Each member of the website has his/her own Category on the Photo Gallery.  This category will not show up until that member creates a new sub-category and 
				uploads at least one photo.  You can not upload photos until you have created a category.  It is best to create a new category each time you upload a new group 
				of photos.  This helps create a more organized Photo Gallery.
			</p>
			<p>&nbsp;</p>
			<div class="top"><a href="#top"><?php echo $LANG['back_top']; ?></a></div>
			<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
			<p><b><a name="gallery-addphoto">How do I add a photo?</a></b></p>
			<ol>
				<li>Choose <a href="gallery/index.php?action=upload">Upload Photos</a> from the <a href="gallery/index.php">Photo Gallery</a> menu.</li>
				<li>Choose a category from the drop down menu.<br/><b>Note:</b> You must have at least one category to upload photos.  If you do not have a existing category you must <a href="#gallery-addcat">add a category</a> first.</li>
				<li>Click the browse button to browse your computer for the desired photo to upload.<br/>If the photo you are uploading needs rotated click <u>Upload Options</u> and two radio buttons will drop in above the photo caption.<br/><b>Note:</b> You must have JavaScript enabled to use the rotation feature.</li>
				<li>Fill in the caption (description of the photo).</li>
				<li>Press the <i>Add Photos</i> button.</li>
			</ol>
			<p>&nbsp;</p>
			<div class="top"><a href="#top"><?php echo $LANG['back_top']; ?></a></div>
			<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>			
			<p><b><a name="gallery-chgphoto">How do I edit/change a photo?</a></b></p>
			<p>You can only edit/change the photo's caption and category.</p>
			<ol>
				<li>Navigate to the photo you would like to edit.</li>
				<li>Click the edit button <img src="themes/images/default/image_edit.gif"/>, located above the photo and to the right.</li>
				<li>
					To change the category: choose the new category from the dropdown menu above the photo.<br/>
					To edit/change the caption: make your changes in the text field area below the photo.
				</li>
				<li>Click the submit changes button to finish your changes.</li>
			</ol>
			<p>&nbsp;</p>
			<div class="top"><a href="#top"><?php echo $LANG['back_top']; ?></a></div>
			<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
			<p><b><a name="gallery-delphoto">How do I delete a photo?</a></b></p>
			<ol>
				<li>Navigate to the photo you would like to edit.</li>
				<li>Click the delete button <img src="themes/images/default/image_delete.gif"/>, located above the photo and to the right.</li>
				<li>You will be prompted with a message asking if you are sure you want to delete that photo, click Ok.</li>
			</ol>
			<p><b>Note:</b> you can only delete your own photos.  Once you delete a photo it is gone forever, you cannot undo a delete.</p>
			<p>&nbsp;</p>
			<div class="top"><a href="#top"><?php echo $LANG['back_top']; ?></a></div>
			<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
			<p><b><a name="gallery-addcat">How do I add a category?</a></b></p>
			<ol>
				<li>Choose <a href="gallery/index.php?action=category">Create/Edit Category</a> from the <a href="gallery/index.php">Photo Gallery</a> menu.</li>
				<li>Fill out the category name.</li>
				<li>Click the <i>Add Category</i> button.</li>
			</ol>
			<p>A list of previously created categories will be listed below.</p>
			<p>&nbsp;</p>
			<div class="top"><a href="#top"><?php echo $LANG['back_top']; ?></a></div>
			<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
			<p><b><a name="gallery-chgcat">How do I rename a category?</a></b></p>
			<ol>
				<li>Choose <a href="gallery/index.php?action=category">Create/Edit Category</a> from the <a href="gallery/index.php">Photo Gallery</a> menu.</li>
				<li>Scroll down to the list of categories and find the one you want to change.</li>
				<li>Make the desired change.</li>
				<li>Click the edit button <img src="themes/images/default/edit.gif"/>, located to the right of the category name.</li>
			</ol>
			<p>&nbsp;</p>
			<div class="top"><a href="#top"><?php echo $LANG['back_top']; ?></a></div>
			<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
			<p><b><a name="gallery-delcat">How do I delete a category?</a></b></p>
			<ol>
				<li>Choose <a href="gallery/index.php?action=category">Create/Edit Category</a> from the <a href="gallery/index.php">Photo Gallery</a> menu.</li>
				<li>Scroll down to the list of categories and find the one you want to delete.</li>
				<li>Click the delete button <img src="themes/images/default/delete.gif"/>, located to the right of the category name.</li>
			</ol>
			<p>&nbsp;</p>
			<div class="top"><a href="#top"><?php echo $LANG['back_top']; ?></a></div>
			<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
			<p><b><a name="settings-avatar">How do I add/change my avatar?</a></b></p>
			<p>&nbsp;</p>
			<div class="top"><a href="#top"><?php echo $LANG['back_top']; ?></a></div>
			<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
			<p><b><a name="address-massemail">How do I email multiple people (Mass Email)?</a></b></p>
			<ol>
				<li>Check the checkboxes to the right of the email addresses of the members you want to email.</li>
				<li>Click the <b>Email</b> button at the bottom right hand corner of the address book.</li>
				<li>Fill out the email form (similar to the contact form) and click <b>Send Mass Email</b>.</li>
			</ol>
			<p>&nbsp;</p>
			<div class="top"><a href="#top"><?php echo $LANG['back_top']; ?></a></div>
			<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
			<p><b><a name="adm-access">Member Access Levels</a></b></p>
			<p>Family Connections has ten different member access levels.  These levels are meant to limit the amount of access each family member has to the website.</p>
			<ol>
				<li><b>Admin</b> - this is the access level given to the account that was setup during the installation of FCMS. This is the only level that has the ability to change other members access levels. This level can add, update and delete all information on the site.</li>
				<li><b>Helper</b> - this access level has all the same priveleges of the Member level, but can also run the latest awards, can add, update and delete poll questions, and add, update and delete message board posts.</li>
				<li><b>Member (default)</b> - this access level can add, update and delete all information they have contributed to the site. They have view only access to other member's information.</li>
				<li><b>Non-Photographer</b> - this access level has all the same priveleges of the Member level, but cannot add, update or delete photos from the Photo Gallery.</li>
				<li><b>Non-Poster</b> - this access level has all the same priveleges of the Member level, but cannot add, update or delete posts from the Message Board.</li>
				<li><b>Commenter</b> - this access level can only add comments to Photos, Family News and can reply to posts on the Message Board.  Has view access to all other sections.</li>
				<li><b>Poster</b> - this access level can add, update and delete their own Message Board posts only.  Has view access to all other sections.</li>
				<li><b>Photographer</b> - this access level can add, update and delete their own Photos only.  Has view access to all other sections.</li>
				<li><b>Blogger</b> - this access level can add, update and delete their own Family News entries only.  Has view access to all other sections.</li>
				<li><b>Guest</b> - this access level has view only access to the site.</li>
			</ol>
			<br/>
			<table class="mem-access" cellpadding="0" cellspacing="0">
				<thead>
					<tr><th rowspan="2">Access Level</th><th colspan="6">Access Rights</th></tr>
					<tr><th>Admininstration</th><th>Photo Gallery</th><th>Message Board</th><th>Address Book</th><th>Family News</th><th>Prayer Concerns</th></tr>
				</thead>
				<tbody>
					<tr><td class="level_name">1. Admin</td><td class="y">Yes*</td><td class="y">Yes*</td><td class="y">Yes*</td><td class="y">Yes*</td><td class="y">Yes*</td><td class="y">Yes*</td></tr>
					<tr><td class="level_name">2. Helper</td><td class="y">Yes^</td><td class="y">Yes</td><td class="y">Yes</td><td class="y">Yes</td><td class="y">Yes</td><td class="y">Yes</td></tr>
					<tr><td class="level_name">3. Member</td><td class="n">No</td><td class="y">Yes</td><td class="y">Yes</td><td class="y">Yes</td><td class="y">Yes</td><td class="y">Yes</td></tr>
					<tr><td class="level_name">4. Non-Photographer</td><td class="n">No</td><td class="n">No</td><td class="y">Yes</td><td class="y">Yes</td><td class="y">Yes</td><td class="y">Yes</td></tr>
					<tr><td class="level_name">5. Non-Poster</td><td class="n">No</td><td class="y">Yes</td><td class="n">No</td><td class="y">Yes</td><td class="y">Yes</td><td class="y">Yes</td></tr>
					<tr><td class="level_name">6. Commenter</td><td class="n">No</td><td>Comment Only</td><td>Comment Only</td><td class="n">No</td><td>Comment Only</td><td class="n">No</td></tr>
					<tr><td class="level_name">7. Poster</td><td class="n">No</td><td class="n">No</td><td class="y">Yes</td><td class="n">No</td><td class="n">No</td><td class="n">No</td></tr>
					<tr><td class="level_name">8. Photographer</td><td class="n">No</td><td class="y">Yes</td><td class="n">No</td><td class="n">No</td><td class="n">No</td><td class="n">No</td></tr>
					<tr><td class="level_name">9. Blogger</td><td class="n">No</td><td class="n">No</td><td class="n">No</td><td class="n">No</td><td class="y">Yes</td><td class="n">No</td></tr>
					<tr><td class="level_name">10. Guest</td><td class="n">No</td><td class="n">No</td><td class="n">No</td><td class="n">No</td><td class="n">No</td><td class="n">No</td></tr>
				</tbody>
			</table>
			<p>* Can add/edit/delete all members information<br/>^ Has limited access to Administration</p>
			<p>&nbsp;</p>
			<div class="top"><a href="#top"><?php echo $LANG['back_top']; ?></a></div>
			<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
		</div><!-- .centercontent -->
	</div><!-- #content -->
	<div id="footer">
		<p>
			<a href="http://www.haudenschilt.com/fcms/" class="ft"><?php echo $LANG['link_home']; ?></a> | <a href="http://www.haudenschilt.com/forum/index.php" class="ft"><?php echo $LANG['link_support']; ?></a> | <a href="help.php" class="ft"><?php echo $LANG['link_help']; ?></a><br />
			<a href="http://www.haudenschilt.com/fcms/"><?php echo $stgs_release; ?></a> - Copyright &copy; 2006/07 Ryan Haudenschilt.  
		</p>
	</div>
</body>
</html>