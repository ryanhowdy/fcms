<?php
/**
 * Configuration
 * 
 * PHP versions 4 and 5
 *
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2010 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

define('URL_PREFIX', '../');

require URL_PREFIX.'fcms.php';

load('admin');

init('admin/');

$currentUserId = cleanInput($_SESSION['login_id'], 'in');
$admin         = new Admin($currentUserId);

// Setup the Template variables;
$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Administration: Configuration'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);
$TMPL['javascript'] = '
<script src="../inc/js/livevalidation.js" type="text/javascript"></script>
<script type="text/javascript">Event.observe(window, "load", function() { initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\'); });</script>';


// Show Header
require_once getTheme($currentUserId, URL_PREFIX).'header.php';

echo '
        <div id="config" class="centercontent">';

if (checkAccess($currentUserId) > 1)
{
    echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                '.T_('This page requires an access level 1 (Admin).').' 
                <a href="../contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';
}
else
{
    echo '
            <div id="leftcolumn">
                <ul class="menu">
                    <li><a href="?view=general">'.T_('General').'</a></li>
                    <li><a href="?view=defaults">'.T_('Defaults').'</a></li>
                    <li><a href="?view=sections">'.T_('Optional Sections').'</a></li>
                    <li><a href="?view=gallery">'.T_('Photo Gallery').'</a></li>
                </ul>
            </div>

            <div id="maincolumn">';

    $show = true;

    //--------------------------------------------------------------------------
    // Update General
    //--------------------------------------------------------------------------
    if (isset($_POST['submit-sitename']))
    {
        if (isset($_POST['sitename']))
        {
            $sql = "UPDATE `fcms_config` 
                    SET `value` = '".cleanInput($_POST['sitename'])."'
                    WHERE `name` = 'sitename'";
            mysql_query($sql) or displaySQLError(
                'Sitename Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
        }

        if (isset($_POST['contact']))
        {
            $sql = "UPDATE `fcms_config` 
                    SET `value` = '".cleanInput($_POST['contact'])."'
                    WHERE `name` = 'contact'";
            mysql_query($sql) or displaySQLError(
                'Contact Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
        }

        if (isset($_POST['activation']))
        {
            $sql = "UPDATE `fcms_config` 
                    SET `value` = '".cleanInput($_POST['activation'])."'
                    WHERE `name` = 'auto_activate'";
            mysql_query($sql) or displaySQLError(
                'Activation Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
        }

        if (isset($_POST['registration']))
        {
            $sql = "UPDATE `fcms_config` 
                    SET `value` = '".cleanInput($_POST['registration'])."'
                    WHERE `name` = 'registration'";
            mysql_query($sql) or displaySQLError(
                'Registration Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
        }

        if (isset($_POST['site_off']))
        {
            $sql = "UPDATE `fcms_config` ";

            if ($_POST['site_off'] == 'yes')
            {
                $sql .= "SET `value` = '1' ";
            }
            else
            {
                $sql .= "SET `value` = '0' ";
            }

            $sql .= "WHERE `name` = 'site_off'";

            mysql_query($sql) or displaySQLError(
                'Site Off Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
        }

        if (isset($_POST['log_errors']))
        {
            $sql = "UPDATE `fcms_config` 
                    SET `value` = '".cleanInput($_POST['log_errors'])."'
                    WHERE `name` = 'log_errors'";
            mysql_query($sql) or displaySQLError(
                'Logging Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
        }

        displayOkMessage();
    }

    //--------------------------------------------------------------------------
    // Update Defaults
    //--------------------------------------------------------------------------
    if (isset($_POST['submit-defaults']))
    {
        $sql = "ALTER TABLE `fcms_user_settings` 
                ALTER `theme` SET DEFAULT '".basename($_POST['theme'])."'";
        mysql_query($sql) or displaySQLError(
            'Theme Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_user_settings` ALTER `showavatar` ";
        if (isset($_POST['showavatar']))
        {
            if ($_POST['showavatar'] == 'yes')
            {
                $sql .= "SET DEFAULT '1'";
            }
            else
            {
                $sql .= "SET DEFAULT '0'";
            }
        }
        mysql_query($sql) or displaySQLError(
            'Show Avatar Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_user_settings` 
                ALTER `displayname` 
                SET DEFAULT '".cleanInput($_POST['displayname'])."'";
        mysql_query($sql) or displaySQLError(
            'Display Name Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_user_settings` 
                ALTER `frontpage` 
                SET DEFAULT '".cleanInput($_POST['frontpage'])."'";
        mysql_query($sql) or displaySQLError(
            'Frontpage Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_user_settings` 
                ALTER `timezone` 
                SET DEFAULT '".cleanInput($_POST['timezone'])."'";
        mysql_query($sql) or displaySQLError(
            'Timezone Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_user_settings` ALTER `dst` ";
        if (isset($_POST['dst']))
        {
            if ($_POST['dst'] == 'on')
            {
                $sql .= "SET DEFAULT '1'";
            }
            else
            {
                $sql .= "SET DEFAULT '0'";
            }
        }
        mysql_query($sql) or displaySQLError(
            'DST Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );
        $sql = "ALTER TABLE `fcms_user_settings` 
                ALTER `boardsort` 
                SET DEFAULT '".cleanInput($_POST['boardsort'])."'";
        mysql_query($sql) or displaySQLError(
            'Board Sort Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );

        // Update existing users
        if (isset($_POST['changeAll']))
        {
            $avatar = isset($upfile) ? $upfile : 'no_avatar.jpg';

            $sql = "UPDATE `fcms_user_settings` 
                    SET `theme` = '".basename($_POST['theme'])."', ";
            if (isset($_POST['showavatar']))
            {
                if ($_POST['showavatar'] == 'yes')
                {
                    $sql .= "`showavatar` = '1', ";
                }
                else
                {
                    $sql .= "`showavatar` = '0', ";
                }
            }
            $sql .= "`displayname`  = '".cleanInput($_POST['displayname'])."', 
                     `frontpage`    = '".cleanInput($_POST['frontpage'])."', 
                     `timezone`     = '".cleanInput($_POST['timezone'])."', ";
            if (isset($_POST['dst']))
            {
                if ($_POST['dst'] == 'on')
                {
                    $sql .= "`dst` = '1', ";
                }
                else
                {
                    $sql .= "`dst` = '0', ";
                }
            }
            $sql .= "`boardsort` = '".cleanInput($_POST['boardsort'])."'";
            mysql_query($sql) or displaySQLError(
                'Update All Users Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
        }
        echo '
            <p class="ok-alert" id="update">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';
    }

    //--------------------------------------------------------------------------
    // Update Optional Sections
    //--------------------------------------------------------------------------
    if (isset($_GET['add']))
    {
        if (ctype_digit($_GET['add']))
        {
            $id = cleanInput($_GET['add'], 'int');

            // Get last order for share and admin, and link of the section we are adding
            $sql = "SELECT MAX(`order`) AS 'col', 'order' AS 'type'
                    FROM `fcms_navigation` 
                    WHERE `col` = 4 
                    UNION
                    SELECT MAX(`order`) AS 'col', 'admin_order' AS 'type'
                    FROM `fcms_navigation` 
                    WHERE `col` = 6 
                    UNION
                    SELECT `link` AS 'col', 'link' AS 'type'
                    FROM `fcms_navigation`
                    WHERE `id` = '$id'";

            $result = mysql_query($sql);
            if (!$result)
            {
                displaySQLError('Last Order Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                return;
            }
            while ($r = mysql_fetch_array($result))
            {
                if ($r['type'] == 'order')
                {
                    $order = $r['col'] + 1;
                }
                elseif ($r['type'] == 'link')
                {
                    $link = $r['col'];
                }
                else
                {
                    $adminOrder = $r['col'] + 1;
                }
            }

            if (!isset($order) || !isset($link) || !isset($adminOrder))
            {
                echo '<p class="error-alert">'.T_('Could not get Order information.').'</p>';
                return;
            }

            // Add after last one
            $sql = "UPDATE `fcms_navigation` 
                    SET `order` = $order 
                    WHERE `id` = '$id'";
            if (!mysql_query($sql))
            {
                displaySQLError('Section Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                return;
            }

            // Add admin for whereiseveryone
            if ($link == 'whereiseveryone')
            {
                $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                        VALUES ('admin_foursquare', 6, $adminOrder, 0)";
                if (!mysql_query($sql))
                {
                    displaySQLError('Admin Section Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                    return;
                }
            }

            // Refresh so it shows up in nav immediately
            echo '<meta http-equiv=\'refresh\' content=\'0;URL=config.php?view=sections\'>';
        }
    }

    //--------------------------------------------------------------------------
    // Remove optional section
    //--------------------------------------------------------------------------
    if (isset($_POST['remove']))
    {
        $id = cleanInput($_POST['remove'], 'int');

        // Get info on section we are removing
        $sql = "SELECT `id`, `link` 
                FROM `fcms_navigation` 
                WHERE `id` = '$id'";

        $result = mysql_query($sql);
        if (!$result)
        {
            displaySQLError('Remove Section Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }
        $info = mysql_fetch_array($result);

        // Remove section
        $sql = "UPDATE `fcms_navigation` 
                SET `order` = 0 
                WHERE `id` = '".cleanInput($_POST['remove'], 'int')."'";
        if (!mysql_query($sql))
        {
            displaySQLError('Remove Section Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            return;
        }

        // If we are deleting whereiseveryone, also delete the admin section
        if ($info['link'] == 'whereiseveryone')
        {
            $sql = "DELETE FROM `fcms_navigation` 
                    WHERE `link` = 'admin_whereiseveryone'";
            if (!mysql_query($sql))
            {
                displaySQLError('Remove Admin Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                return;
            }
        }

        // Get the current nav order
        $sql = "SELECT `id`, `order` 
                FROM `fcms_navigation` 
                WHERE `col` = 4 
                ORDER BY `order`";

        $result = mysql_query($sql)  or displaySQLError(
            'Remove Section Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );

        if (mysql_num_rows($result) > 0)
        {
            $i = 1;
            while ($r = mysql_fetch_array($result))
            {
                if ($r['order'] != 0)
                {
                    // Update the new order
                    $sql = "UPDATE `fcms_navigation` 
                            SET `order` = '$i' 
                            WHERE `id` = '".$r['id']."'";
                    mysql_query($sql) or displaySQLError(
                        'Update Order Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
                    );
                    $i++;
                }
            }
        }

        // Refresh so it removes from nav immediately
        echo '<meta http-equiv=\'refresh\' content=\'0;URL=config.php?view=sections\'>';
    }

    //--------------------------------------------------------------------------
    // Update optional section order 
    //--------------------------------------------------------------------------
    if (isset($_POST['submit-sections']))
    {
        // Validate proper order
        $communicateOrders = array();
        $shareOrders       = array();
        foreach ($_POST as $key => $value)
        {
            $comPos = strpos($key, 'com-order');
            if ($comPos !== false)
            {
                $communicateOrders[] = $value;
            }
            $sharePos = strpos($key, 'share-order');
            if ($sharePos !== false)
            {
                $shareOrders[] = $value;
            }
        }

        $communicateCount1 = count($communicateOrders);
        $communicateOrders = array_unique($communicateOrders);
        $communicateCount2 = count($communicateOrders);

        if ($communicateCount1 != $communicateCount2)
        {
            echo '
            <p class="error-alert" id="update">'.T_('Invalid Order for "Communicate".').'<br/>'.T_('You cannot have two sections with the same order.').'</p>';
        }

        $shareCount1 = count($shareOrders);
        $shareOrders = array_unique($shareOrders);
        $shareCount2 = count($shareOrders);

        if ($shareCount1 != $shareCount2)
        {
            echo '
            <p class="error-alert">'.T_('Invalid Order for "Share".').'<br/>'.T_('You cannot have two sections with the same order.').'</p>';
        }

        unset($_POST['submit-sections']);

        // Update the order
        foreach ($_POST as $key => $value)
        {
            $arr = explode('_', $key);
            $id  = end($arr);
            $sql = "UPDATE `fcms_navigation` 
                    SET `order` = '".cleanInput($value, 'int')."' 
                    WHERE `id` = '".cleanInput($id, 'int')."'";
            if (!mysql_query($sql))
            {
                displaySQLError('Update Order Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
                return;
            }
        }

        displayOkMessage();
    }

    //-------------------------------------------------------------------------
    // Update Photo Gallery
    //-------------------------------------------------------------------------
    if (isset($_POST['submit-gallery']))
    {
        $sql = "UPDATE `fcms_config` 
                SET `value` = '".cleanInput($_POST['full_size_photos'])."'
                WHERE `name` = 'full_size_photos'";
        mysql_query($sql) or displaySQLError(
            'Full Size Photos Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
        );
        echo '
            <p class="ok-alert" id="update">'.T_('Changes Updated Successfully').'</p>
            <script type="text/javascript">
                window.onload=function(){ var t=setTimeout("$(\'update\').toggle()",3000); }
            </script>';
    }

    //-------------------------------------------------------------------------
    // Add new sections
    //-------------------------------------------------------------------------
    if (isset($_GET['addsection']))
    {
        // Family News
        if ($_GET['addsection'] == 'news')
        {
            $sql = "CREATE TABLE `fcms_news` (
                        `id` int(11) NOT NULL auto_increment, 
                        `title` varchar(50) NOT NULL default '', 
                        `news` text NOT NULL, 
                        `user` int(11) NOT NULL default '0', 
                        `date` datetime NOT NULL default '0000-00-00 00:00:00', 
                        PRIMARY KEY (`id`), 
                        KEY `userindx` (`user`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            mysql_query($sql) or displaySQLError(
                'New News Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
            $sql = "ALTER TABLE `fcms_news` 
                    ADD CONSTRAINT `fcms_news_ibfk_1` 
                    FOREIGN KEY (`user`) 
                    REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
            mysql_query($sql) or displaySQLError(
                'Alter News Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
            $sql = "CREATE TABLE `fcms_news_comments` (
                        `id` int(11) NOT NULL auto_increment, 
                        `news` int(11) NOT NULL default '0', 
                        `comment` text NOT NULL, 
                        `date` timestamp NOT NULL default '0000-00-00 00:00:00', 
                        `user` int(11) NOT NULL default '0', 
                        PRIMARY KEY  (`id`), 
                        KEY `photo_ind` (`news`), 
                        KEY `user_ind` (`user`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            mysql_query($sql) or displaySQLError(
                'New News Comments Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
            $sql = "ALTER TABLE `fcms_news_comments` 
                    ADD CONSTRAINT `fcms_news_comments_ibfk_2` 
                    FOREIGN KEY (`user`) 
                    REFERENCES `fcms_users` (`id`) 
                    ON DELETE CASCADE, 

                    ADD CONSTRAINT `fcms_news_comments_ibfk_1` 
                    FOREIGN KEY (`news`) 
                    REFERENCES `fcms_news` (`id`) ON DELETE CASCADE";
            mysql_query($sql) or displaySQLError(
                'Alter News Comments Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
        }

        // Prayer Concerns
        if ($_GET['addsection'] == 'prayers')
        {
            $sql = "CREATE TABLE `fcms_prayers` (
                        `id` int(11) NOT NULL auto_increment, 
                        `for` varchar(50) NOT NULL default '', 
                        `desc` text NOT NULL, 
                        `user` int(11) NOT NULL default '0', 
                        `date` datetime NOT NULL default '0000-00-00 00:00:00', 
                        PRIMARY KEY (`id`), 
                        KEY `userindx` (`user`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            mysql_query($sql) or displaySQLError(
                'New Prayers Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
            $sql = "ALTER TABLE `fcms_prayers` 
                    ADD CONSTRAINT `fcms_prayers_ibfk_1` 
                    FOREIGN KEY (`user`) 
                    REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
            mysql_query($sql) or displaySQLError(
                'Alter Prayers Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
        }

        // Recipes
        if ($_GET['addsection'] == 'recipes')
        {
            $sql = "CREATE TABLE `fcms_recipes` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT, 
                        `category` VARCHAR(50) NOT NULL, 
                        `name` VARCHAR(50) NOT NULL DEFAULT 'My Recipe', 
                        `recipe` TEXT NOT NULL, 
                        `user` INT(11) NOT NULL, 
                        `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            mysql_query($sql) or displaySQLError('New Recipe Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
            $sql = "ALTER TABLE `fcms_recipes` 
                    ADD CONSTRAINT `fcms_recipes_ibfk_1` 
                    FOREIGN KEY (`user`) 
                    REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
            mysql_query($sql) or displaySQLError(
                'Alter Recipe Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
        }

        // Documents
        if ($_GET['addsection'] == 'documents')
        {
            $sql = "CREATE TABLE `fcms_documents` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT, 
                        `name` VARCHAR(50) NOT NULL, 
                        `description` TEXT NOT NULL, 
                        `user` INT(11) NOT NULL, 
                        `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            mysql_query($sql) or displaySQLError(
                'New Documents Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
            $sql = "ALTER TABLE `fcms_documents` 
                    ADD CONSTRAINT `fcms_documents_ibfk_1` 
                    FOREIGN KEY (`user`) 
                    REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
            mysql_query($sql) or displaySQLError(
                'Alter Documents Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error()
            );
        }
    }

    //-------------------------------------------------------------------------
    // Display Config forms 
    //-------------------------------------------------------------------------
    if ($show)
    {
        $view = isset($_GET['view']) ? cleanInput($_GET['view']) : 'general';
        $admin->displayAdminConfig($view);
    }
}

echo '
            </div>

        </div><!-- .centercontent -->';

// Show Footer
require_once getTheme($currentUserId, URL_PREFIX).'footer.php';
