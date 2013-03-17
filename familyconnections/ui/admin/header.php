<!DOCTYPE html>
<html lang="<?php echo T_('lang'); ?>">
<head>
<meta charset="utf-8">
<title><?php echo $TMPL['sitename'] . " - " . T_('powered by') . " " . $TMPL['version']; ?></title>
<meta name="author" content="Ryan Haudenschilt" />
<link rel="shortcut icon" href="<?php echo $TMPL['path']; ?>themes/favicon.ico"/>
<link href="../ui/twitter-bootstrap/bootstrap.min.css" rel="stylesheet">
<link href="<?php echo $TMPL['path']; ?>ui/admin/style.css" rel="stylesheet"/>
<style>body { padding-top: 40px; }</style>
<?php if (isset($TMPL['javascript'])) { echo $TMPL['javascript']; } ?>
</head>
<body id="top">

    <div class="topbar">
        <div class="fill">
            <div class="container">
                <a class="brand" href="../home.php"><?php echo '&laquo; '.T_('Back to Site'); ?></a>
                <ul class="nav">
                    <li><a href="index.php"><?php echo T_('Dashboard'); ?></a></li>
                    <li><a href="upgrade.php"><?php echo T_('Upgrade'); ?></a></li>
                    <li><a href="config.php"><?php echo T_('Configuration'); ?></a></li>
                    <li><a href="members.php"><?php echo T_('Members'); ?></a></li>
                    <li><a href="gallery.php"><?php echo T_('Photo Gallery'); ?></a></li>
                    <li><a href="polls.php"><?php echo T_('Polls'); ?></a></li>
                    <li><a href="scheduler.php"><?php echo T_('Scheduler'); ?></a></li>
                </ul>
            </div>
        </div>
    </div>

    <h1 id="page-header">
        <div class="container"><?php echo $TMPL['pagetitle']; ?></div>
    </h1>

    <div class="container">
