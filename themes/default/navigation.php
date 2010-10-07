    <div id="topmenu">
        <ul id="navigation">
            <li class="main"><a class="main" href="<?php echo $TMPL['path'].'index.php';?>"><?php echo T_('Home');?></a></li>
            <li class="main dropdown"><a class="main" href="<?php echo $TMPL['path'].'profile.php';?>"><?php echo T_('My Stuff');?><![if gt IE 6]></a><![endif]><!--[if lte IE 6]><table><tr><td><![endif]-->
            <ul class="sub">
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'profile.php';?>"><?php echo T_('Profile');?></a></li>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'settings.php';?>"><?php echo T_('Settings');?></a></li>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'privatemsg.php';?>"><?php echo T_('Private Messages').getPMCount();?></a></li>
            </ul>
            <!--[if lte IE 6]></td></tr></table></a><![endif]--></li>
            <li class="main dropdown"><a class="main" href="<?php echo $TMPL['path'].$TMPL['nav-link'][3][0]['url'];?>"><?php echo T_('Communicate');?><![if gt IE 6]></a><![endif]><!--[if lte IE 6]><table><tr><td><![endif]-->
            <ul class="sub">
                <?php foreach($TMPL['nav-link'][3] AS $nav):?>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].$nav['url'];?>"><?php echo $nav['text'];?></a></li>
                <?php endforeach; ?>
            </ul>
            <!--[if lte IE 6]></td></tr></table></a><![endif]--></li>
            <li class="main dropdown"><a class="main" href="<?php echo $TMPL['path'].$TMPL['nav-link'][4][0]['url'];?>"><?php echo T_('Share');?><![if gt IE 6]></a><![endif]><!--[if lte IE 6]><table><tr><td><![endif]-->
            <ul class="sub">
                <?php foreach($TMPL['nav-link'][4] AS $nav):?>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].$nav['url'];?>"><?php echo $nav['text'];?></a></li>
                <?php endforeach; ?>

            </ul>
            <!--[if lte IE 6]></td></tr></table></a><![endif]--></li>
            <li class="main dropdown"><a class="main" href="<?php echo $TMPL['path'].'members.php';?>"><?php echo T_('Misc.');?><![if gt IE 6]></a><![endif]><!--[if lte IE 6]><table><tr><td><![endif]-->
            <ul class="sub">
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'members.php';?>"><?php echo T_('Members');?></a></li>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'contact.php';?>"><?php echo T_('Contact Webmaster');?></a></li>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'help.php';?>"><?php echo T_('Help');?></a></li>
            </ul>
            <!--[if lte IE 6]></td></tr></table></a><![endif]--></li>
        <?php if (checkAccess($currentUserId) <= 2): ?>
            <li class="main dropdown"><a class="main" href="<?php echo $TMPL['path'].'admin/config.php';?>"><?php echo T_('Administration');?><![if gt IE 6]></a><![endif]><!--[if lte IE 6]><table><tr><td><![endif]-->
            <ul class="sub">
            <?php if (checkAccess($currentUserId) < 2): ?>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'admin/upgrade.php';?>"><?php echo T_('Upgrade');?></a></li>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'admin/config.php';?>"><?php echo T_('Configuration');?></a></li>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'admin/members.php';?>"><?php echo T_('Members');?></a></li>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'admin/gallery.php';?>"><?php echo T_('Photo Gallery');?></a></li>
            <?php endif; ?>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'admin/polls.php';?>"><?php echo T_('Polls');?></a></li>
                <li class="sub"><a class="sub" href="<?php echo $TMPL['path'].'admin/awards.php';?>"><?php echo T_('Awards');?></a></li>
            </ul>
            <!--[if lte IE 6]></td></tr></table></a><![endif]--></li>
        <?php endif; ?>
        </ul>
    </div>
