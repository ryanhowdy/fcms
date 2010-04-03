    <div id="topmenu">
        <ul id="navigation">
            <li class="main"><a class="main" href="<?php echo $TMPL['path'].'index.php';?>"><?php echo _('Home');?></a></li>
            <li class="main dropdown"><a class="main" href="<?php echo $TMPL['path'].'profile.php?member='.$current_user_id;?>"><?php echo _('My Stuff');?><![if gt IE 6]></a><![endif]><!--[if lte IE 6]><table><tr><td><![endif]-->
            <ul class="sub">
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'profile.php?member='.$current_user_id;?>"><?php echo _('Profile');?></a></li>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'settings.php';?>"><?php echo _('Settings');?></a></li>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'privatemsg.php';?>"><?php echo _('Private Messages').getPMCount();?></a></li>
            </ul>
            <!--[if lte IE 6]></td></tr></table></a><![endif]--></li>
            <li class="main dropdown"><a class="main" href="<?php echo $TMPL['path'].'messageboard.php';?>"><?php echo _('Communicate');?><![if gt IE 6]></a><![endif]><!--[if lte IE 6]><table><tr><td><![endif]-->
            <ul class="sub">
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'messageboard.php';?>"><?php echo _('Message Board');?></a></li>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'chat.php';?>"><?php echo _('Chat Room');?></a></li>
            </ul>
            <!--[if lte IE 6]></td></tr></table></a><![endif]--></li>
            <li class="main dropdown"><a class="main" href="<?php echo $TMPL['path'].$TMPL['default-url'];?>"><?php echo _('Share');?><![if gt IE 6]></a><![endif]><!--[if lte IE 6]><table><tr><td><![endif]-->
            <ul class="sub">

                <?php foreach($TMPL['nav-link'] AS $nav):?>

                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].$nav['url'];?>"><?php echo $nav['text'];?></a></li>

                <?php endforeach; ?>

            </ul>
            <!--[if lte IE 6]></td></tr></table></a><![endif]--></li>
            <li class="main dropdown"><a class="main" href="<?php echo $TMPL['path'].'contact.php';?>"><?php echo _('Help');?><![if gt IE 6]></a><![endif]><!--[if lte IE 6]><table><tr><td><![endif]-->
            <ul class="sub">
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'contact.php';?>"><?php echo _('Contact Webmaster');?></a></li>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'help.php';?>"><?php echo _('Help');?></a></li>
            </ul>
            <!--[if lte IE 6]></td></tr></table></a><![endif]--></li>

            <?php if (checkAccess($current_user_id) <= 2): ?>

            <li class="main dropdown"><a class="main" href="<?php echo $TMPL['path'].'admin/config.php';?>"><?php echo _('Administration');?><![if gt IE 6]></a><![endif]><!--[if lte IE 6]><table><tr><td><![endif]-->
            <ul class="sub">

                <?php if (checkAccess($current_user_id) < 2): ?>

                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'admin/upgrade.php';?>"><?php echo _('Upgrade');?></a></li>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'admin/config.php';?>"><?php echo _('Configuration');?></a></li>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'admin/members.php';?>"><?php echo _('Members');?></a></li>

                <?php endif; ?>

                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'admin/board.php';?>"><?php echo _('Message Board');?></a></li>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'admin/polls.php';?>"><?php echo _('Polls');?></a></li>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'admin/awards.php';?>"><?php echo _('Awards');?></a></li>
            </ul>
            <!--[if lte IE 6]></td></tr></table></a><![endif]--></li>

            <?php endif; ?>
        </ul>
    </div>
