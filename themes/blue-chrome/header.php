<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo T_('lang'); ?>" lang="<?php echo T_('lang'); ?>">
<head>
<title><?php echo $TMPL['sitename'] . " - " . T_('powered by') . " " . $TMPL['version']; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="author" content="Ryan Haudenschilt" />
<link rel="shortcut icon" href="<?php echo $TMPL['path']; ?>themes/favicon.ico"/>
<link rel="stylesheet" type="text/css" href="<?php echo $TMPL['path']; ?>themes/blue-chrome/style.css"/>
<!--[if IE 5.5]>
<link rel="stylesheet" type="text/css" href="<?php echo $TMPL['path']; ?>themes/blue-chrome/ie55.css"/>
<![endif]-->
<!--[if IE 6]>
<link rel="stylesheet" type="text/css" href="<?php echo $TMPL['path']; ?>themes/blue-chrome/ie6.css"/>
<![endif]-->
<!--[if IE 7]>
<link rel="stylesheet" type="text/css" href="<?php echo $TMPL['path']; ?>themes/blue-chrome/ie7.css"/>
<![endif]-->
<!--[if IE 8]>
<link rel="stylesheet" type="text/css" href="<?php echo $TMPL['path']; ?>themes/blue-chrome/ie8.css"/>
<![endif]-->
<script type="text/javascript" src="<?php echo $TMPL['path']; ?>inc/js/prototype.js"></script>
<script type="text/javascript" src="<?php echo $TMPL['path']; ?>inc/js/fcms.js<?php echo '?'.time();?>"></script>
<?php if (isset($TMPL['javascript'])) { echo $TMPL['javascript']; } ?>
</head>
<body id="top">

    <div id="header">
        <div id="logo">
            <a href="<?php echo $TMPL['path']; ?>index.php">
                <img src="<?php echo $TMPL['path']; ?>themes/blue-chrome/images/logo.gif" alt="<?php echo $TMPL['sitename'];?>"/>
            </a>
        </div>
        <p>
            <?php echo T_('Welcome'); ?> <a href="<?php echo $TMPL['path'] . "profile.php"; ?>"><?php echo $TMPL['displayname']; ?></a> <?php displayNewPM($currentUserId, $TMPL['path']); ?> | 
            <a href="<?php echo $TMPL['path'] . "settings.php";?>"><?php echo T_('My Settings'); ?></a> | 
            <a href="<?php echo $TMPL['path'] . "logout.php"; ?>"><?php echo T_('Logout'); ?></a>
        </p>
    </div>

<?php include_once('navigation.php'); ?>

    <!-- ############ CONTENT START ############ -->
    <div id="content">

        <div id="pagetitle"><?php echo $TMPL['pagetitle']; ?></div>
