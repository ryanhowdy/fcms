        <h2 class="firstmenu"><?php echo $LANG['navigation']; ?></h2>
        <div class="firstmenu menu">
            <ul>
                <?php 
                $sections = countOptSections();
                if ($sections > 0) {
                for ($i = 1; $i < $sections; $i++) { ?>
                <li><a href="<?php echo $TMPL['path']; displayOptSection($i+1, 1, 'URL'); ?>"><?php displayOptSection($i+1, 1); ?></a></span></li>
                <?php } } ?>
                <li><a href="<?php echo $TMPL['path'] . 'profile.php'; ?>"><?php echo $LANG['link_profiles']; ?></a></li>
                <li><a href="<?php echo $TMPL['path'] . 'contact.php'; ?>"><?php echo $LANG['link_contact']; ?></a></li>
                <li><a href="<?php echo $TMPL['path'] . 'help.php'; ?>"><?php echo $LANG['link_get_help']; ?></a></li>
                <li><a href="<?php echo $TMPL['path'] . 'logout.php'; ?>"><?php echo $LANG['link_logout']; ?></a></li>
            </ul>
        </div>
		<?php if (isset($gallery)) { $gallery->displaySideMenu(); } ?>
