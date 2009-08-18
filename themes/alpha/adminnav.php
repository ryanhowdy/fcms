        <h2 class="adminmenu"><?php echo $LANG['admin']; ?></h2>
        <div class="adminmenu menu">
            <ul>
                <?php if (checkAccess($_SESSION['login_id']) < 2) { ?>
                <li><a href="<?php echo $admin_d . 'config.php'; ?>"><?php echo $LANG['link_admin_config']; ?></a></li>
                <li><a href="<?php echo $admin_d . 'members.php'; ?>"><?php echo $LANG['link_admin_members']; ?></a></li>
                <li><a href="<?php echo $admin_d . 'board.php'; ?>"><?php echo $LANG['link_admin_board']; ?></a></li>
                <?php } ?>
                <li><a href="<?php echo $admin_d . 'polls.php'; ?>"><?php echo $LANG['link_admin_polls']; ?></a></li>
                <li><a href="<?php echo $admin_d . 'awards.php'; ?>"><?php echo $LANG['link_admin_awards']; ?></a></li>
                <?php if (checkAccess($_SESSION['login_id']) < 2) { ?>
                <li><a href="<?php echo $admin_d . 'upgrade.php'; ?>"><?php echo $LANG['link_admin_upgrade']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
