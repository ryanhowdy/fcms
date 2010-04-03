<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo _('lang'); ?>" lang="<?php echo _('lang'); ?>">
<head>
<title><?php echo getSiteName() . " - " . _('powered by') . " " . getCurrentVersion(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="author" content="Ryan Haudenschilt" />
<link rel="shortcut icon" href="<?php echo $TMPL['path']; ?>themes/favicon.ico"/>
<link rel="stylesheet" type="text/css" href="<?php echo getTheme($_SESSION['login_id'], $TMPL['path']); ?>style.css"/>
<script type="text/javascript" src="<?php echo $TMPL['path']; ?>inc/prototype.js"></script>
<script type="text/javascript" src="<?php echo $TMPL['path']; ?>inc/fcms.js"></script>
<?php if (isset($TMPL['javascript'])) { echo $TMPL['javascript']; } ?>
</head>
<body id="top">

    <div id="header">
        <div id="logo">
            <a href="<?php echo $TMPL['path']; ?>index.php">
                <img src="<?php echo $TMPL['path']; ?>themes/default/images/logo.jpg" alt="<?php echo getSiteName();?>"/>
            </a>
        </div>
        <p>
            <?php echo _('Welcome'); ?> <a href="<?php echo $TMPL['path'] . "profile.php?member=" . $_SESSION['login_id']; ?>"><?php echo getUserDisplayName($_SESSION['login_id']); ?></a> <?php displayNewPM($_SESSION['login_id'], $TMPL['path']); ?> | 
            <a href="<?php echo $TMPL['path'] . "settings.php";?>"><?php echo _('My Settings'); ?></a> | 
            <a href="<?php echo $TMPL['path'] . "logout.php"; ?>"><?php echo _('Logout'); ?></a>
        </p>
    </div>

<?php $TMPL['default-url'] = getDefaultNavUrl(); $TMPL['nav-link'] = getNavLinks(); include_once('navigation.php'); ?>

    <!-- ############ CONTENT START ############ -->
    <div id="content">

        <div id="pagetitle"><?php echo $TMPL['pagetitle']; ?></div>
