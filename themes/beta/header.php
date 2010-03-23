<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo getSiteName() . " - " . $LANG['poweredby'] . " " . getCurrentVersion(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="author" content="Ryan Haudenschilt" />
<link rel="shortcut icon" href="<?php echo $TMPL['path']; ?>themes/favicon.ico"/>
<link rel="stylesheet" type="text/css" href="<?php echo getTheme($_SESSION['login_id'], $TMPL['path']); ?>style.css"/>
<script type="text/javascript" src="<?php echo $TMPL['path']; ?>inc/prototype.js"></script>
<script type="text/javascript" src="<?php echo $TMPL['path']; ?>inc/fcms.js"></script>
<?php if (isset($TMPL['javascript'])) { echo $TMPL['javascript']; } ?> 
</head>
<body class="yui-skin-sam">

    <div><a name="top"></a></div>

    <div id="header">
        <h1 id="logo"><?php echo getSiteName(); ?></h1>
        <p>
            <?php echo $LANG['welcome']; ?>
            <a href="<?php echo $TMPL['path'] . "profile.php?member=" . $_SESSION['login_id']; ?>"><?php echo getUserDisplayName($_SESSION['login_id']); ?></a>
            <?php displayNewPM($_SESSION['login_id'], $TMPL['path']); ?> | 
            <a href="<?php echo $TMPL['path'] . "settings.php";?>"><?php echo $LANG['link_settings']; ?></a> | 
            <a href="<?php echo $TMPL['path'] . "logout.php"; ?>"><?php echo $LANG['link_logout']; ?></a>
        </p>
    </div>

    <div id="topmenu">
        <ul id="navlist">
            <li><span><a class="firstnavmenu" href="<?php echo $TMPL['path'] . 'home.php';?>"><?php echo $LANG['link_home']; ?></a></span></li>
            <li><span><a class="navmenu" href="<?php echo $TMPL['path'] . 'gallery/index.php'; ?>"><?php echo $LANG['link_gallery']; ?></a></span></li>
            <li><span><a class="navmenu" href="<?php echo $TMPL['path'] . 'messageboard.php'; ?>"><?php echo $LANG['link_board']; ?></a></span></li>
            <li><span><a class="navmenu" href="<?php echo $TMPL['path'] . 'addressbook.php'; ?>"><?php echo $LANG['link_address']; ?></a></span></li>
            <li><span><a class="navmenu" href="<?php echo $TMPL['path'] . 'calendar.php';?>"><?php echo $LANG['link_calendar']; ?></a></span></li>
            <?php
            if (countOptSections() > 0) { ?>
            <li><span><a class="lastnavmenu" href="<?php echo $TMPL['path']; displayOptSection(1, 1, 'URL'); ?>"><?php displayOptSection(1, 1); ?></a></span></li>
            <?php } else { ?>
            <li><span><a class="lastnavmenu" href="#">&nbsp;</a></span></li>
            <?php } ?>
        </ul>
    </div>

    <div id="pagetitle"><?php echo $TMPL['pagetitle']; ?></div>
