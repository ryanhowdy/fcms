<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo T_pgettext('Language Code for this translation', 'lang'); ?>" lang="<?php echo T_pgettext('Language Code for this translation', 'lang'); ?>">
<head>
<title><?php echo $TMPL['sitename'] . " - " . T_('powered by') . " " . $TMPL['version']; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta name="author" content="Ryan Haudenschilt" />
<meta name="viewport" content="width=device-width" />
<link rel="shortcut icon" href="<?php echo $TMPL['path']; ?>ui/favicon.ico"/>
<link rel="stylesheet" type="text/css" href="<?php echo $TMPL['path']; ?>css/bootstrap337.min.css"/>
<link rel="stylesheet" type="text/css" href="<?php echo $TMPL['path']; ?>css/legacy.css?v=400"/>
<link rel="stylesheet" type="text/css" media="only screen and (max-width: 480px)" href="<?php echo $TMPL["path"]; ?>ui/themes/default/css/mobile.css?v=380"/>
<script type="text/javascript" src="<?php echo $TMPL['path']; ?>js/legacy/jquery1.11.0.js"></script>
<script type="text/javascript" src="<?php echo $TMPL['path']; ?>js/legacy/bootstrap.min.js"></script>
<script type="text/javascript" src="<?php echo $TMPL['path']; ?>js/legacy/fcms.js?v=400"></script>
<?php if (isset($TMPL['javascript'])) { echo $TMPL['javascript']; } ?>
</head>
<body id="top">

    <div id="header">
        <div id="logo">
            <a href="<?php echo $TMPL['path']; ?>home.php">
                <img src="<?php echo $TMPL['path']; ?>/img/logo.gif" alt="<?php echo $TMPL['sitename'];?>"/>
            </a>
        </div>
        <p>
            <?php echo T_('Welcome'); ?> <a href="<?php echo $TMPL['path'] . "profile.php"; ?>"><?php echo $TMPL['displayname']; ?></a> <?php displayNewPM($TMPL['currentUserId'], $TMPL['path']); ?> | 
            <a href="<?php echo $TMPL['path'] . "settings.php";?>"><?php echo T_('My Settings'); ?></a> | 
            <a href="<?php echo $TMPL['path'] . "logout.php"; ?>"><?php echo T_('Logout'); ?></a>
        </p>
    </div>

<?php require_once('navigation.php'); ?>

    <div id="content">
        <div id="pagetitle"><?php echo $TMPL['pagetitle']; ?></div>
        <div id="<?php echo $TMPL['pageId']; ?>" class="centercontent">
