<!--
Family Connections - a family oriented CMS - http://www.familycms.com/

Copyright (C) 2007-09 Ryan Haudenschilt

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
-->
<?php
include_once('inc/gettext.inc');
// Setup php-gettext
T_setlocale(LC_MESSAGES, 'en_US');
bindtextdomain('messages', './language');
if (function_exists('bind_textdomain_codeset')) {
  bind_textdomain_codeset('messages', 'UTF-8');
}
textdomain('messages');
if (get_magic_quotes_gpc()) {
    $_REQUEST = array_map('stripslashes', $_REQUEST);
    $_GET = array_map('stripslashes', $_GET);
    $_POST = array_map('stripslashes', $_POST);
    $_COOKIE = array_map('stripslashes', $_COOKIE);
}
echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'._('lang').'" lang="'._('lang').'">
<head>
<title>Family Connections '._('Installation').'</title>
<link rel="stylesheet" type="text/css" href="themes/fcms-core.css" />
<script type="text/javascript" src="inc/prototype.js"></script>
<script type="text/javascript" src="inc/livevalidation.js"></script>
</head>
<body>';

if (
    !isset($_POST['submit1']) && 
    !isset($_POST['submit2']) && 
    !isset($_POST['submit3']) && 
    !isset($_POST['submit4']) && 
    !isset($_POST['submit5']) && 
    file_exists('inc/config_inc.php')
) {
    echo '
    <div id="install">
        <h1>'._('Wait a Minute.').'</h1>
        <p>'._('The installation for this site is already finished. Running the installation again will cause you to Lose All Your Previous Information.  Are you sure you want to continue?').'</p>
        <div class="clearfix">
            <div class="option">
                <a class="nbtn" href="index.php">'._('No').'</a><br/><br/>
                '._('I just want to view my site.').'
            </div>
            <div class="option">
                <a class="ybtn" href="#" onclick="$(\'show-install\').toggle(); 
                    $(\'install\').toggle(); document.setupform.dbhost.focus(); return false">'._('Yes').'</a><br/><br/>
                '._('I want to run the installation anyway. (Not Recommended)').'
            </div>
        </div>
        <p>&nbsp;</p>
        <br/>
    </div>
    <div id="show-install" style="display:none;">';
}
// Step Two
if (isset($_POST['submit1'])) {
    displayStepTwo();
// Step Three
} else if (isset($_POST['submit2'])) {
    if (empty($_POST['dbhost']) || empty($_POST['dbname']) || empty($_POST['dbuser']) || empty($_POST['dbpass'])) {
        echo '
        <script type="text/javascript">
        Event.observe(window, \'load\', function() { $(\'dbhost\').focus(); });
        </script>';
        displayStepTwo("<p class=\"error\">"._('You forgot a required field.  Please fill out all required fields.')."</p>");
    } else {
        $file = fopen('inc/config_inc.php', 'w') or die("<h1>Error Creating Config File</h1>");
        $str = "<?php \$cfg_mysql_host = '".$_POST['dbhost']."'; \$cfg_mysql_db = '".$_POST['dbname']."'; \$cfg_mysql_user = '".$_POST['dbuser']."'; \$cfg_mysql_pass = '".$_POST['dbpass']."'; ?".">";
        fwrite($file, $str);
        fclose($file);
        displayStepThree();
    }
// Step Four
} else if (isset($_POST['submit3'])) {
        echo <<<HTML
        <script type="text/javascript">
        Event.observe(window, 'load', function() {
            $('sitename').focus();
        });
        </script>

HTML;
    displayStepFour();
// Step Five
} else if (isset($_POST['submit4'])) {
    if (empty($_POST['sitename']) || empty($_POST['contact'])) {
        displayStepFour("<p class=\"error\">"._('You forgot a required field.  Please fill out all required fields.')."</p>");
    } else {
        include_once('inc/config_inc.php');
        mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
        mysql_select_db($cfg_mysql_db);

        // Setup Config
        mysql_query("TRUNCATE TABLE `fcms_config`") or die(mysql_error());
        $sql = "INSERT INTO `fcms_config` (
                    `sitename`, `contact`, `current_version`
                ) VALUES (
                    '".escape_string($_POST['sitename'])."', '".escape_string($_POST['contact'])."', 'Family Connections 2.2.2'
                )";
        mysql_query($sql) or die($sql . "<br/><br/>" . mysql_error());

        // Setup Navigation
        $nextOrder = 4;
        $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`) VALUES ";
        // Family News
        $order = 0;
        if (isset($_POST['sections-news'])) {
            $order = $nextOrder;
            $nextOrder++;
        }
        $sql .= "('familynews', 4, $order, 0), ";
        // Recipes
        $order = 0;
        if ($_POST['sections-recipes']) {
            $order = $nextOrder;
            $nextOrder++;
        }
        $sql .= "('recipes', 4, $order, 0), ";
        // Documents
        $order = 0;
        if ($_POST['sections-documents']) {
            $order = $nextOrder;
            $nextOrder++;
        }
        $sql .= "('documents', 4, $order, 0), ";
        // Prayers
        $order = 0;
        if ($_POST['sections-prayers']) {
            $order = $nextOrder;
            $nextOrder++;
        }
        $sql .= "('prayers', 4, $order, 0)";
        mysql_query($sql) or die($sql . "<br/><br/>" . mysql_error());

        displayStepFive();
    }
// Finish
} else if (isset($_POST['submit5'])) {
    if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['fname']) || empty($_POST['lname']) || empty($_POST['email'])) {
        displayStepFive("<p class=\"error\">"._('You forgot a required field.  Please fill out all required fields.')."</p>");
    } else {
        $birthday = $_POST['year'] . "-" . str_pad($_POST['month'], 2, "0", STR_PAD_LEFT) . "-" . str_pad($_POST['day'], 2, "0", STR_PAD_LEFT);
        setupDatabase($_POST['fname'], $_POST['lname'], $_POST['email'], $_POST['username'], $_POST['password'], $birthday);
    }
} else {
    displayStepOne();
    echo "</div>";
} ?>
</body>
</html>
<?php
function is__writable($path) {
//will work in despite of Windows ACLs bug
//NOTE: use a trailing slash for folders!!!
//see http://bugs.php.net/bug.php?id=27609
//see http://bugs.php.net/bug.php?id=30931
    if ($path{strlen($path)-1}=='/') // recursively return a temporary file path
        return is__writable($path.uniqid(mt_rand()).'.tmp');
    else if (@is_dir($path))
        return is__writable($path.'/'.uniqid(mt_rand()).'.tmp');
    // check tmp file for read/write capabilities
    $rm = file_exists($path);
    $f = @fopen($path, 'a');
    if ($f===false)
        return false;
    fclose($f);
    if (!$rm)
        unlink($path);
    return true;
}
function displayStepOne () {
    // Check inc
    $check_inc = false;
    if (is__writable('inc/')) {
        $inc = "<span class=\"ok\">"._('OK')."</span>";
        $check_inc = true;
    } else {
        $inc = "<span class=\"bad\">"._('BAD')."</span>";
    }
    // Check avatar
    $check_avatar = false;
    if (is__writable('gallery/avatar/')) {
        $avatar = "<span class=\"ok\">"._('OK')."</span>";
        $check_avatar = true;
    } else {
        $avatar = "<span class=\"bad\">"._('BAD')."</span>";
    }
    // Check documents
    $check_docs = false;
    if (is__writable('inc/')) {
        $docs = "<span class=\"ok\">"._('OK')."</span>";
        $check_docs = true;
    } else {
        $docs = "<span class=\"bad\">"._('BAD')."</span>";
    }
    // Check photos
    $check_photos = false;
    if (is__writable('inc/')) {
        $photos = "<span class=\"ok\">"._('OK')."</span>";
        $check_photos = true;
    } else {
        $photos = "<span class=\"bad\">"._('BAD')."</span>";
    }
    // Check upimages
    $check_up = false;
    if (is__writable('inc/')) {
        $up = "<span class=\"ok\">"._('OK')."</span>";
        $check_up = true;
    } else {
        $up = "<span class=\"bad\">"._('BAD')."</span>";
    }
    echo '
    <div id="column">
        <h1>'._('Install').' Family Connections</h1>
        <h2>'._('Pre-Installation Check').'</h2>
        <form class="nofields" action="install.php" method="post">
            <div style="text-align:center">'._('Step 1 of 5').'</div><div class="progress"><div style="width:20%"></div></div>
            <div><b>'._('Checking Folder Permissions').'</b></div>
            <div><div class="dir">inc/</div> <div class="status">'.$inc.'</div></div>
            <div style="clear:both;"></div>
            <div><div class="dir">gallery/avatar/</div> <div class="status">'.$avatar.'</div></div>
            <div style="clear:both;"></div>
            <div><div class="dir">gallery/documents/</div> <div class="status">'.$docs.'</div></div>
            <div style="clear:both;"></div>
            <div><div class="dir">gallery/photos/</div> <div class="status">'.$photos.'</div></div>
            <div style="clear:both;"></div>
            <div><div class="dir">gallery/upimages/</div> <div class="status">'.$up.'</div></div>
            <div style="clear:both;"></div>';
        if ($check_inc && $check_avatar && $check_docs && $check_photos && $check_up) {
            echo '
            <div>'._('Your site is ready to be installed.  Please proceed to the next step.').'</div>
            <p style="text-align:right;"><input id="submit" name="submit1" type="submit"  value="'._('Next').' >>"/></p>
            <div class="clear"></div>';
        } else {
            echo '
            <div>'._('Unfortunatly your site is not ready to be installed.  Please make sure that the folders above exist and have the proper permissions set.').'</div>';
        }
        echo '
        </form>
    </div>';
}
function displayStepTwo ($error = '0') { ?>
    <div id="column">
        <h1><?php echo _('Install'); ?> Family Connections</h1>
        <h2><?php echo _('Database Information'); ?></h2>
        <div style="text-align:center"><?php echo _('Step 2 of 5'); ?></div><div class="progress"><div style="width:40%"></div></div>
        <?php if ($error !== '0') { echo $error; } ?>
        <form action="install.php" method="post">
        <div>
            <div class="field-label"><label for="dbhost"><b><?php echo _('Database Host'); ?></b> <span class="req">*</span></label></div>
            <div class="field-widget">
                <input type="text" name="dbhost" id="dbhost"/>
                <div><?php echo _('This is usually localhost or your database ip address.'); ?></div>
            </div>
        </div>
        <script type="text/javascript">
            var fdbhost = new LiveValidation('dbhost', { onlyOnSubmit: true });
            fdbhost.add(Validate.Presence, {failureMessage: "<?php echo _('Sorry, but I can\'t install without this information.'); ?>"});
        </script>
        <div>
            <div class="field-label"><label for="dbname"><b><?php echo _('Database Name'); ?></b> <span class="req">*</span></label></div>
            <div class="field-widget">
                <input type="text" name="dbname" id="dbname"/>
                <div><?php echo sprintf(_('The database name where you want to install %s.'), 'Family Connections'); ?></div>
            </div>
        </div>
        <script type="text/javascript">
            var fdbname = new LiveValidation('dbname', { onlyOnSubmit: true });
            fdbname.add(Validate.Presence, {failureMessage: "<?php echo _('Sorry, but I can\'t install without this information.'); ?>"});
        </script>
        <div>
            <div class="field-label"><label for="dbuser"><b><?php echo _('Database Username'); ?></b> <span class="req">*</span></label></div>
            <div class="field-widget">
                <input type="text" name="dbuser" id="dbuser"/>
                <div><?php echo _('The username for the database specified above.'); ?></div>
            </div>
        </div>
        <script type="text/javascript">
            var fdbuser = new LiveValidation('dbuser', { onlyOnSubmit: true });
            fdbuser.add(Validate.Presence, {failureMessage: "<?php echo _('There has to be a user for this database.'); ?>"});
        </script>     
        <div>
            <div class="field-label"><label for="dbpass"><b><?php echo _('Database Password'); ?></b> <span class="req">*</span></label></div>
            <div class="field-widget">
                <input type="password" name="dbpass" id="dbpass"/>
                <div><?php echo _('The password for the database specified above.'); ?></div>
            </div>
        </div>
        <script type="text/javascript">
            var fdbpass = new LiveValidation('dbpass', { onlyOnSubmit: true });
            fdbpass.add(Validate.Presence, {failureMessage: "<?php echo _('Passwords are hard to remember, but unfortunately necessary.'); ?>"});
        </script>
        <p style="text-align:right;"><input id="submit" name="submit2" type="submit"  value="<?php echo _('Next'); ?> >>"/></p>
        <div class="clear"></div>
        </form>
    </div>
<?php
}
function displayStepThree () {
    include_once('inc/config_inc.php');
    echo '
    <div id="column">
        <h1>'._('Install').'Family Connections</h1>
        <form class="nofields" action="install.php" method="post">
        <h2>'._('Checking Database Connection').'</h2>
        <div style="text-align:center">'._('Step 3 of 5').'</div><div class="progress"><div style="width:60%"></div></div>
        <div>
            '.sprintf(_('Attempting to connect to database %s on %s using user %s.'), "<i>$cfg_mysql_db</i>", "<i>$cfg_mysql_host</i>", "<i>$cfg_mysql_user</i>").'
        </div>';
    $connection = @mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
    if (!$connection) {
        die('<h3 class="bad">'._('Uh-Oh!').'</h3><div>'._('A connection to the database could not be made.  Please shut down your browser and then re-run the installation.').'</div>');
    } else {
        mysql_select_db($cfg_mysql_db) or die("<h1>Error</h1><p><b>Connection made, but database could not be found!</b></p>" . mysql_error());
        echo '
        <h3>'._('Awesome!').'</h3>
        <div>'._('A connection was successfully made to the database.  Please proceed to the next step.').'</div>';
        mysql_query("DROP TABLE IF EXISTS `fcms_config`") or die("<h1>Error</h1><p><b>Could not drop `fcms_config` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_navigation`") or die("<h1>Error</h1><p><b>Could not drop `fcms_navigation` table.</b></p>" . mysql_error());
        // Create fcms_config
        $sql = "CREATE TABLE `fcms_config` (
                    `sitename` VARCHAR(50) NOT NULL DEFAULT 'My Site', 
                    `contact` VARCHAR(50) NOT NULL DEFAULT 'nobody@yoursite.com', 
                    `current_version` VARCHAR(50) NOT NULL DEFAULT 'Family Connections', 
                    `auto_activate` TINYINT(1) NOT NULL DEFAULT 0, 
                    `full_size_photos` TINYINT(1) NOT NULL DEFAULT 0,
                    `site_off` TINYINT(1) NOT NULL DEFAULT '0'
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // create fcms_navigation
        $sql = "CREATE TABLE `fcms_navigation` (
                    `id` INT(25) NOT NULL AUTO_INCREMENT,
                    `link` VARCHAR(30) NOT NULL,
                    `col` TINYINT(1) NOT NULL,
                    `order` TINYINT(2) NOT NULL,
                    `req` TINYINT(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // insert fcms_navigation
        $sql = "INSERT INTO `fcms_navigation` (`link`, `col`, `order`, `req`)
                VALUES ('profile', 2, 1, 1),
                    ('settings', 2, 2, 1),
                    ('pm', 2, 3, 1),
                    ('messageboard', 3, 1, 1),
                    ('chat', 3, 2, 1),
                    ('photogallery', 4, 1, 1),
                    ('addressbook', 4, 2, 1),
                    ('calendar', 4, 3, 1)";
        mysql_query($sql) or displaySQLError(mysql_error());
    }
    echo '
        <p style="text-align:right;"><input id="submit" name="submit3" type="submit"  value="'._('Next').' >>"/></p>
        <div class="clear"></div>
        </form>
    </div>';
}
function displayStepFour ($error = '0') { ?>
    <div id="column">
        <h1><?php echo _('Install'); ?> Family Connections</h1>
        <h2><?php echo _('Website Information'); ?></h2>
        <div style="text-align:center"><?php echo _('Step 4 of 5'); ?></div><div class="progress"><div style="width:80%"></div></div>
        <form action="install.php" method="post">
        <div>
            <div class="field-label"><label for="sitename"><b><?php echo _('Website Name');?></b> <span class="req">*</span></label>
            <div class="field-widget big">
                <input type="text" name="sitename" id="sitename" title="<?php echo _('What do you want your website to be called?'); ?>"/>
                <div><?php echo _('(Examples: "The Smith\'s" or "The Johnson Family Website")'); ?></div>
            </div>
        </div>
        <script type="text/javascript">
            var fsitename = new LiveValidation('sitename', { onlyOnSubmit: true });
            fsitename.add(Validate.Presence, {failureMessage: "<?php echo _('Without a name, how will you tell people about your website?'); ?>"});
        </script>
        <div>
            <div class="field-label"><label for="contact"><b><?php echo _('Contact Email'); ?></b> <span class="req">*</span></label></div>
            <div class="field-widget big">
                <input type="text" name="contact" id="contact"/>
                <div><?php echo _('The email address you want all questions, comments and concerns about the site to go.'); ?></div>
            </div>
        </div>
        <script type="text/javascript">
            var fcontact = new LiveValidation('contact', { onlyOnSubmit: true });
            fcontact.add( Validate.Presence, { failureMessage: "<?php echo _('Sorry, but this information is required.'); ?>" } );
            fcontact.add( Validate.Email, { failureMessage: "<?php echo _('That\'s not a valid emaill address is it?'); ?>" } );
            fcontact.add( Validate.Length, { minimum: 10 } );
        </script>
        <div>
            <div class="field-label"><label><b><?php echo _('Optional Sections'); ?></b></label></div>
            <div>
                <input type="checkbox" name="sections-news" id="sections-news" value="familynews"/>
                <label for="sections-news"><?php echo _('Family News'); ?></label><br/>
                <input type="checkbox" name="sections-recipes" id="sections-recipes" value="recipes"/>
                <label for="sections-recipes"><?php echo _('Recipes'); ?></label><br/>
                <input type="checkbox" name="sections-documents" id="sections-documents" value="documents"/>
                <label for="sections-documents"><?php echo _('Documents'); ?></label><br/>
                <input type="checkbox" name="sections-prayers" id="sections-prayers" value="prayerconcerns"/>
                <label for="sections-prayers"><?php echo _('Prayer Concerns'); ?></label>
            </div>
        </div>
        <p><?php echo _('Which sections would you like to use on your site?'); ?></p>
        <p style="text-align:right;"><input id="submit" name="submit4" type="submit"  value="<?php echo _('Next'); ?> >>"/></p>
        <div class="clear"></div>
        </form>
    </div>
<?php
}
function displayStepFive ($error = '0') {
    include_once('inc/config_inc.php');
    global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;
    include_once('inc/locale.php');
    $locale = new Locale(); ?>
    <div id="column">
        <h1><?php echo _('Install'); ?> Family Connections</h1>
        <h2><?php echo _('Administrative Account'); ?></h2>
        <div style="text-align:center"><?php echo _('Step 5 of 5'); ?></div><div class="progress"><div style="width:100%"></div></div>
        <form action="install.php" method="post">
        <p><?php echo _('Everyone will be required to have an account and be logged in at all times to use this website.  This will help protect your site.'); ?></p>
        <p><?php echo _('You must have at least one administrative account.  Please fill out the information below for the person who will be the administrator of this site.'); ?></p>
        <div>
            <div class="field-label"><label for="username"><b><?php echo _('Username'); ?></b> <span class="req">*</span></label></div>
            <div class="field-widget"><input type="text" name="username" id="username"/></div>
        </div>
        <script type="text/javascript">
            var funame = new LiveValidation('username', { onlyOnSubmit: true });
            funame.add(Validate.Presence, {failureMessage: "<?php echo _('Sorry, but I can\'t install without this information.'); ?>"});
        </script>
        <div>
            <div class="field-label"><label for="password"><b><?php echo _('Password'); ?></b> <span class="req">*</span></label></div>
            <div class="field-widget"><input type="password" name="password" id="password"/></div>
        </div>
        <script type="text/javascript">
            var fpass = new LiveValidation('password', { onlyOnSubmit: true });
            fpass.add(Validate.Presence, {failureMessage: "<?php echo _('Passwords are hard to remember, but unfortunately necessary.'); ?>"});
        </script>
        <div>
            <div class="field-label"><label for="fname"><b><?php echo _('First Name'); ?></b> <span class="req">*</span></label></div>
            <div class="field-widget"><input type="text" name="fname" id="fname"/></div>
        </div>
        <script type="text/javascript">
            var ffname = new LiveValidation('fname', { onlyOnSubmit: true });
            ffname.add(Validate.Presence, {failureMessage: "<?php echo _('Sorry, but I can\'t install without this information.'); ?>"});
        </script>
        <div>
            <div class="field-label"><label for="lname"><b><?php echo _('Last Name'); ?></b> <span class="req">*</span></label></div>
            <div class="field-widget"><input type="text" name="lname" id="lname"/></div>
        </div>
        <script type="text/javascript">
            var flname = new LiveValidation('lname', { onlyOnSubmit: true });
            flname.add(Validate.Presence, {failureMessage: "<?php echo _('Sorry, but I can\'t install without this information.'); ?>"});
        </script>
        <div>
            <div class="field-label"><label for="email"><b><?php echo _('Email Address'); ?></b> <span class="req">*</span></label></div>
            <div class="field-widget"><input type="text" name="email" id="email"/></div>
        </div>
        <script type="text/javascript">
            var femail = new LiveValidation('email', { onlyOnSubmit: true });
            femail.add( Validate.Presence, { failureMessage: "<?php echo _('Sorry, but this information is required.'); ?>" } );
            femail.add( Validate.Email, { failureMessage: "<?php echo _('That\'s not a valid email address is it?'); ?>" } );
            femail.add( Validate.Length, { minimum: 10 } );
        </script>
        <div>
            <div class="field-label"><label for="day"><b><?php echo _('Birthday'); ?></b><span class="req">*</span></label></div>
            <div class="field-widget">
                <select id="day" name="day">
                    <?php
                    $d = 1;
                    while ($d <= 31) {
                        if ($day == $d) { echo "<option value=\"$d\" selected=\"selected\">$d</option>"; }
                        else { echo "<option value=\"$d\">$d</option>"; }
                        $d++;
                    }
                    echo '</select><select id="month" name="month">';
                    $m = 1;
                    while ($m <= 12) {
                        if ($month == $m) {
                            echo "<option value=\"$m\" selected=\"selected\">" . $locale->getMonthAbbr($m) . "</option>";
                        } else {
                            echo "<option value=\"$m\">" . $locale->getMonthAbbr($m) . "</option>";
                        }
                        $m++;
                    }
                    echo '</select><select id="year" name="year">';
                    $y = 1900;
                    while ($y - 5 <= date('Y')) {
                        if ($year == $y) { echo "<option value=\"$y\" selected=\"selected\">$y</option>"; }
                        else { echo "<option value=\"$y\">$y</option>"; }
                        $y++;
                    } ?>
                </select>
            </div>
        </div>
        <p style="text-align:right;"><input id="submit" name="submit5" type="submit"  value="<?php echo _('Next'); ?> >>"/></p>
        <div class="clear"></div>
        </form>
    </div>
<?php
}
function setupDatabase ($fname, $lname, $email, $username, $password, $birthday) {
    include_once('inc/config_inc.php');
    include_once('inc/util_inc.php');
    $password = md5($password);
    $connection = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
    if (!$connection) {
        die("<h1>Connection Error [" . __FILE__ . __LINE__ . "]</h1>" . mysql_error());
    } else {
        mysql_select_db($cfg_mysql_db) or die("<h1>Error</h1><p><b>Database could not be found!</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_chat_users`") or die("<h1>Error</h1><p><b>Could not drop 'fcms_chat' table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_chat_messages`") or die("<h1>Error</h1><p><b>Could not drop 'fcms_chat_messages' table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_address`") or die("<h1>Error</h1><p><b>Could not drop `fcms_address` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_alerts`") or die("<h1>Error</h1><p><b>Could not drop `fcms_alerts` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_privatemsg`") or die("<h1>Error</h1><p><b>Could not drop `fcms_privatemsg` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_documents`") or die("<h1>Error</h1><p><b>Could not drop `fcms_documents` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_calendar`") or die("<h1>Error</h1><p><b>Could not drop `fcms_calendar` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_gallery_comments`") or die("<h1>Error</h1><p><b>Could not drop `fcms_gallery_comments` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_gallery_photos_tags`") or die("<h1>Error</h1><p><b>Could not drop `fcms_gallery_photos_tags` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_gallery_photos`") or die("<h1>Error</h1><p><b>Could not drop `fcms_gallery_photos` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_gallery_category`") or die("<h1>Error</h1><p><b>Could not drop `fcms_gallery_category` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_news_comments`") or die("<h1>Error</h1><p><b>Could not drop `fcms_news_comments` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_news`") or die("<h1>Error</h1><p><b>Could not drop `fcms_news` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_poll_votes`") or die("<h1>Error</h1><p><b>Could not drop `fcms_poll_users` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_poll_options`") or die("<h1>Error</h1><p><b>Could not drop `fcms_poll_options` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_polls`") or die("<h1>Error</h1><p><b>Could not drop `fcms_polls` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_prayers`") or die("<h1>Error</h1><p><b>Could not drop `fcms_prayers` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_board_posts`") or die("<h1>Error</h1><p><b>Could not drop `fcms_board_posts` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_board_threads`") or die("<h1>Error</h1><p><b>Could not drop `fcms_board_threads` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_recipes`") or die("<h1>Error</h1><p><b>Could not drop `fcms_recipes` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_user_awards`") or die("<h1>Error</h1><p><b>Could not drop `fcms_user_awards` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_user_settings`") or die("<h1>Error</h1><p><b>Could not drop `fcms_user_settings` table.</b></p>" . mysql_error());
        mysql_query("DROP TABLE IF EXISTS `fcms_users`") or die("<h1>Error</h1><p><b>Could not drop `fcms_users` table.</b></p>" . mysql_error());
        mysql_query("SET NAMES utf8") or die("<h1>Error</h1><p><b>Could not set encoding</b></p>" . mysql_error());
        // create users
        $sql = "CREATE TABLE `fcms_users` (
                    `id` INT(25) NOT NULL AUTO_INCREMENT, 
                    `access` TINYINT(1) NOT NULL DEFAULT '3', 
                    `activity` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    `joindate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                    `fname` VARCHAR(25) NOT NULL DEFAULT 'fname', 
                    `lname` VARCHAR(25) NOT NULL DEFAULT 'lname', 
                    `email` VARCHAR(50) NOT NULL DEFAULT 'me@mail.com', 
                    `birthday` DATE NOT NULL DEFAULT '0000-00-00', 
                    `username` VARCHAR(25) NOT NULL DEFAULT '0', 
                    `password` VARCHAR(255) NOT NULL DEFAULT '0', 
                    `avatar` VARCHAR(25) NOT NULL DEFAULT 'no_avatar.jpg', 
                    `activate_code` CHAR(13) NULL, 
                    `activated` TINYINT(1) NOT NULL DEFAULT '0', 
                    `login_attempts` TINYINT(1) NOT NULL DEFAULT '0', 
                    `locked` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    PRIMARY KEY (`id`), 
                    UNIQUE KEY `username` (`username`)
                )
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // insert users
        $sql = "INSERT INTO `fcms_users` (
                    `id`, `access`, `joindate`, `fname`, `lname`, `email`, `birthday`, `username`, `password`, `activated`
                ) VALUES (
                    1, 
                    1, 
                    NOW(), 
                    '".escape_string($fname)."', 
                    '".escape_string($lname)."', 
                    '".escape_string($email)."', 
                    '".escape_string($birthday)."', 
                    '".escape_string($username)."', 
                    '$password', 
                    1
                )";
        mysql_query($sql) or die(mysql_error());
        // create user_settings
        $sql = "CREATE TABLE `fcms_user_settings` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `user` INT(11) NOT NULL, 
                    `theme` VARCHAR(25) NOT NULL DEFAULT 'default', 
                    `boardsort` SET('ASC', 'DESC') NOT NULL DEFAULT 'ASC', 
                    `showavatar` TINYINT(1) NOT NULL DEFAULT '1', 
                    `displayname` SET('1','2','3') NOT NULL DEFAULT '1', 
                    `frontpage` SET('1','2') NOT NULL DEFAULT '1', 
                    `timezone` set('-12 hours', '-11 hours', '-10 hours', '-9 hours', '-8 hours', '-7 hours', '-6 hours', '-5 hours', '-4 hours', '-3 hours -30 minutes', '-3 hours', '-2 hours', '-1 hours', '-0 hours', '+1 hours', '+2 hours', '+3 hours', '+3 hours +30 minutes', '+4 hours', '+4 hours +30 minutes', '+5 hours', '+5 hours +30 minutes', '+6 hours', '+7 hours', '+8 hours', '+9 hours', '+9 hours +30 minutes', '+10 hours', '+11 hours', '+12 hours') NOT NULL DEFAULT '-5 hours', 
                    `dst` TINYINT(1) NOT NULL DEFAULT '0', 
                    `email_updates` TINYINT(1) NOT NULL DEFAULT '0', 
                    `advanced_upload` TINYINT(1) NOT NULL DEFAULT '0',
                    `language` VARCHAR(6) NOT NULL DEFAULT 'en_US',
                    PRIMARY KEY (`id`), 
                    KEY `user_ind` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter user_settings
        $sql = "ALTER TABLE `fcms_user_settings` 
                ADD CONSTRAINT `fcms_user_stgs_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // insert user_settings
        $sql = "INSERT INTO `fcms_user_settings` (`id`, `user`) 
                VALUES (NULL, 1)";
        mysql_query($sql) or die(mysql_error());
        // create address
        $sql = "CREATE TABLE `fcms_address` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    `entered_by` INT(11) NOT NULL DEFAULT '0', 
                    `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                    `address` VARCHAR(50) DEFAULT NULL, 
                    `city` VARCHAR(50) DEFAULT NULL, 
                    `state` VARCHAR(50) DEFAULT NULL, 
                    `zip` VARCHAR(10) DEFAULT NULL, 
                    `home` VARCHAR(20) DEFAULT NULL, 
                    `work` VARCHAR(20) DEFAULT NULL, 
                    `cell` VARCHAR(20) DEFAULT NULL, 
                    PRIMARY KEY (`id`), 
                    KEY `user_ind` (`user`), 
                    KEY `ent_ind` (`entered_by`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter address
        $sql = "ALTER TABLE `fcms_address` 
                ADD CONSTRAINT `fcms_address_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // insert address
        $sql = "INSERT INTO `fcms_address` (`id`, `user`, `entered_by`) 
                VALUES (NULL, 1, 1)";
        mysql_query($sql) or die(mysql_error());
        // create calendar
        $sql = "CREATE TABLE `fcms_calendar` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `date` DATE NOT NULL DEFAULT '0000-00-00', 
                    `date_added` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    `title` VARCHAR(50) NOT NULL DEFAULT 'MyDate', 
                    `desc` TEXT, 
                    `created_by` INT(11) NOT NULL DEFAULT '0', 
                    `type` SET('Birthday','Anniversary','Holiday','Other') NOT NULL DEFAULT 'Other', 
                    `private` TINYINT(1) NOT NULL DEFAULT '0', 
                    PRIMARY KEY  (`id`), 
                    KEY `by_ind` (`created_by`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter calendar
        $sql = "ALTER TABLE `fcms_calendar` 
                ADD CONSTRAINT `fcms_calendar_ibfk_1` 
                FOREIGN KEY (`created_by`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // TODO
        // These need gettext
        // insert calendar
        $sql = "INSERT INTO `fcms_calendar` 
                    (`id`, `date`, `title`, `desc`, `created_by`, `type`) 
                VALUES 
                    (NULL, '".escape_string($birthday)."', '".escape_string($fname)." ".escape_string($lname)."', NULL, 1, 'Birthday'), 
                    (NULL, '2007-12-25', 'Christmas', NULL, 1, 'Holiday'), 
                    (NULL, '2007-02-14', 'Valentine''s Day', NULL, 1, 'Holiday'), 
                    (NULL, '2007-01-01', 'New Year''s Day', NULL, 1, 'Holiday'), 
                    (NULL, '2007-07-04', 'Independence Day', NULL, 1, 'Holiday'), 
                    (NULL, '2007-02-02', 'Groundhog Day', NULL, 1, 'Holiday'), 
                    (NULL, '2007-03-17', 'St. Patrick''s Day', NULL, 1, 'Holiday'), 
                    (NULL, '2007-04-01', 'April Fools Day', NULL, 1, 'Holiday'), 
                    (NULL, '2007-10-31', 'Halloween', NULL, 1, 'Holiday')";
        mysql_query($sql) or die(mysql_error());
        // create gallery_category
        $sql = "CREATE TABLE `fcms_gallery_category` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `name` VARCHAR(50) NOT NULL DEFAULT 'category', 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `user_ind` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter gallery_category
        $sql = "ALTER TABLE `fcms_gallery_category` 
                ADD CONSTRAINT `fcms_gallery_category_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // create gallery_photos
        $sql = "CREATE TABLE `fcms_gallery_photos` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    `filename` VARCHAR(25) NOT NULL DEFAULT 'noimage.gif', 
                    `caption` TEXT, 
                    `category` INT(11) NOT NULL DEFAULT '0', 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    `views` SMALLINT(6) NOT NULL DEFAULT '0', 
                    `votes` SMALLINT(6) NOT NULL DEFAULT '0', 
                    `rating` FLOAT NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `cat_ind` (`category`), 
                    KEY `user_ind` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter gallery_photos
        $sql = "ALTER TABLE `fcms_gallery_photos` 
                ADD CONSTRAINT `fcms_gallery_photos_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE, 
                ADD CONSTRAINT `fcms_gallery_photos_ibfk_2` 
                FOREIGN KEY (`category`) 
                REFERENCES `fcms_gallery_category` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // create gallery_comments
        $sql = "CREATE TABLE `fcms_gallery_comments` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `photo` INT(11) NOT NULL DEFAULT '0', 
                    `comment` TEXT NOT NULL, 
                    `date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `photo_ind` (`photo`), 
                    KEY `user_ind` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter gallery_comments
        $sql = "ALTER TABLE `fcms_gallery_comments` 
                ADD CONSTRAINT `fcms_gallery_comments_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE, 
                ADD CONSTRAINT `fcms_gallery_comments_ibfk_2` 
                FOREIGN KEY (`photo`) 
                REFERENCES `fcms_gallery_photos` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // create gallery_photos_tags
        $sql = "CREATE TABLE `fcms_gallery_photos_tags` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    `photo` INT(11) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `tag_photo_ind` (`photo`), 
                    KEY `tag_user_ind` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter gallery_photos_tags
        $sql = "ALTER TABLE `fcms_gallery_photos_tags` 
                ADD CONSTRAINT `fcms_gallery_photos_tags_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE, 
                ADD CONSTRAINT `fcms_gallery_photos_tags_ibfk_2` 
                FOREIGN KEY (`photo`) 
                REFERENCES `fcms_gallery_photos` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // create news
        $sql = "CREATE TABLE `fcms_news` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `title` VARCHAR(50) NOT NULL DEFAULT '', 
                    `news` TEXT NOT NULL, 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    PRIMARY KEY (`id`), 
                    KEY `userindx` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter news
        $sql = "ALTER TABLE `fcms_news` 
                ADD CONSTRAINT `fcms_news_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // create news_comments
        $sql = "CREATE TABLE `fcms_news_comments` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `news` INT(11) NOT NULL DEFAULT '0', 
                    `comment` TEXT NOT NULL, 
                    `date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `photo_ind` (`news`), 
                    KEY `user_ind` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter news_comments
        $sql = "ALTER TABLE `fcms_news_comments` 
                ADD CONSTRAINT `fcms_news_comments_ibfk_2` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE, 
                ADD CONSTRAINT `fcms_news_comments_ibfk_1` 
                FOREIGN KEY (`news`) 
                REFERENCES `fcms_news` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // create polls
        $sql = "CREATE TABLE `fcms_polls` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `question` TEXT NOT NULL, 
                    `started` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    PRIMARY KEY  (`id`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // insert poll
        $sql = "INSERT INTO `fcms_polls` (`id`, `question`, `started`) 
                VALUES (NULL, 'Family Connections software is...', NOW())";
        mysql_query($sql) or die(mysql_error());
        // create poll_options
        $sql = "CREATE TABLE `fcms_poll_options` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `poll_id` INT(11) NOT NULL DEFAULT '0', 
                    `option` TEXT NOT NULL, 
                    `votes` INT(11) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `pollid_ind` (`poll_id`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter poll_options
        $sql = "ALTER TABLE `fcms_poll_options` 
                ADD CONSTRAINT `fcms_poll_options_ibfk_1` 
                FOREIGN KEY (`poll_id`) 
                REFERENCES `fcms_polls` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // insert poll_options
        $sql = "INSERT INTO `fcms_poll_options` (`id`, `poll_id`, `option`, `votes`) 
                VALUES 
                    (NULL, 1, 'Easy to use!', 0), 
                    (NULL, 1, 'Visually appealing!', 0), 
                    (NULL, 1, 'Just what our family needed!', 0)";
        mysql_query($sql) or die(mysql_error());
        // create poll_votes
        $sql = "CREATE TABLE `fcms_poll_votes` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    `option` INT(11) NOT NULL DEFAULT '0', 
                    `poll_id` INT(11) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `user_ind` (`user`), 
                    KEY `option_ind` (`option`), 
                    KEY `poll_id_ind` (`poll_id`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter poll_votes
        $sql = "ALTER TABLE `fcms_poll_votes` 
                ADD CONSTRAINT `fcms_poll_votes_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE, 
                ADD CONSTRAINT `fcms_poll_votes_ibfk_2` 
                FOREIGN KEY (`option`) 
                REFERENCES `fcms_poll_options` (`id`) 
                ON DELETE CASCADE,  
                ADD CONSTRAINT `fcms_poll_votes_ibfk_3` 
                FOREIGN KEY (`poll_id`) 
                REFERENCES `fcms_polls` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // create prayers
        $sql = "CREATE TABLE `fcms_prayers` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `for` VARCHAR(50) NOT NULL DEFAULT '', 
                    `desc` TEXT NOT NULL, 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    PRIMARY KEY (`id`), 
                    KEY `userindx` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter prayers
        $sql = "ALTER TABLE `fcms_prayers` 
                ADD CONSTRAINT `fcms_prayers_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // create board_threads
        $sql = "CREATE TABLE `fcms_board_threads` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `subject` VARCHAR(50) NOT NULL DEFAULT 'Subject', 
                    `started_by` INT(11) NOT NULL DEFAULT '0', 
                    `updated` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    `updated_by` INT(11) NOT NULL DEFAULT '0', 
                    `views` SMALLINT(6) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `start_ind` (`started_by`), 
                    KEY `up_ind` (`updated_by`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter board_threads
        $sql = "ALTER TABLE `fcms_board_threads` 
                ADD CONSTRAINT `fcms_threads_ibfk_1` 
                FOREIGN KEY (`started_by`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE, 
                ADD CONSTRAINT `fcms_threads_ibfk_2` 
                FOREIGN KEY (`updated_by`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // insert board_threads
        $sql = "INSERT INTO `fcms_board_threads` (`id`, `subject`, `started_by`, `updated`, `updated_by`, `views`) 
                VALUES (1, '"._('Welcome')."', 1, NOW(), 1, 0)";
        mysql_query($sql) or die(mysql_error());
        // create board_posts
        $sql = "CREATE TABLE `fcms_board_posts` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    `thread` INT(11) NOT NULL DEFAULT '0', 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    `post` TEXT NOT NULL, 
                    PRIMARY KEY (`id`), 
                    KEY `thread_ind` (`thread`), 
                    KEY `user_ind` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // altert board_posts
        $sql = "ALTER TABLE `fcms_board_posts` 
                ADD CONSTRAINT `fcms_posts_ibfk_1` 
                FOREIGN KEY (`thread`) 
                REFERENCES `fcms_board_threads` (`id`) 
                ON DELETE CASCADE, 
                ADD CONSTRAINT `fcms_posts_ibfk_2` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // insert board_posts
        $sql = "INSERT INTO `fcms_board_posts` (`id`, `date`, `thread`, `user`, `post`) 
                VALUES (NULL, NOW(), 1, 1, '".sprintf(_('Welcome to the %s Message Board.'), 'Family Connections')."')";
        mysql_query($sql) or die(mysql_error());
        // create recipes
        $sql = "CREATE TABLE `fcms_recipes` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `category` VARCHAR(50) NOT NULL, 
                    `name` VARCHAR(50) NOT NULL DEFAULT 'My Recipe', 
                    `recipe` TEXT NOT NULL, 
                    `user` INT(11) NOT NULL, 
                    `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                    PRIMARY KEY (`id`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter recipes
        $sql = "ALTER TABLE `fcms_recipes` 
                ADD CONSTRAINT `fcms_recipes_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // create privatemsg
        $sql = "CREATE TABLE `fcms_privatemsg` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `to` INT(11) NOT NULL, 
                    `from` INT(11) NOT NULL, 
                    `date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', 
                    `title` VARCHAR(50) NOT NULL DEFAULT 'PM Title', 
                    `msg` TEXT, 
                    `read` TINYINT(1) NOT NULL DEFAULT '0', 
                    PRIMARY KEY (`id`), 
                    KEY `to_ind` (`to`), 
                    KEY `from_ind` (`from`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter privatemsg
        $sql = "ALTER TABLE `fcms_privatemsg` 
                ADD CONSTRAINT `fcms_privatemsg_ibfk_1` 
                FOREIGN KEY (`to`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE, 
                ADD CONSTRAINT `fcms_privatemsg_ibfk_2` 
                FOREIGN KEY (`from`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // create documents
        $sql = "CREATE TABLE `fcms_documents` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `name` VARCHAR(50) NOT NULL, 
                    `description` TEXT NOT NULL, 
                    `user` INT(11) NOT NULL, 
                    `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
                    PRIMARY KEY (`id`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter documents
        $sql = "ALTER TABLE `fcms_documents` 
                ADD CONSTRAINT `fcms_documents_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // create chat_users
        $sql = "CREATE TABLE `fcms_chat_users` (
                    `user_name` VARCHAR(64) DEFAULT NULL,
                    `time` DATETIME NOT NULL
                ) 
                ENGINE=INNODB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // create chat_messages
        $sql = "CREATE TABLE `fcms_chat_messages` (
                    `message_id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `chat_id` INT(11) NOT NULL DEFAULT '0', 
                    `user_id` INT(11) NOT NULL DEFAULT '0', 
                    `user_name` VARCHAR(64) DEFAULT NULL, 
                    `message` TEXT, 
                    `post_time` DATETIME DEFAULT NULL, 
                    PRIMARY KEY (`message_id`)
                ) 
                ENGINE=INNODB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // create user_awards
        $sql = "CREATE TABLE `fcms_user_awards` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT, 
                    `user` INT(11) NOT NULL DEFAULT '0', 
                    `type` VARCHAR(20) NOT NULL DEFAULT '0', 
                    `value` SMALLINT(4) NOT NULL DEFAULT '0', 
                    `count` SMALLINT(4) NOT NULL default '0', 
                    PRIMARY KEY (`id`), 
                    KEY `user` (`user`)
                ) 
                ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        // alter user_awards
        $sql = "ALTER TABLE `fcms_user_awards` 
                ADD CONSTRAINT `fcms_user_awards_ibfk_1` 
                FOREIGN KEY (`user`) 
                REFERENCES `fcms_users` (`id`) 
                ON DELETE CASCADE";
        mysql_query($sql) or die(mysql_error());
        // insert user_awards
        $sql = "INSERT INTO `fcms_user_awards` 
                    (`id`, `user`, `type`, `value`, `count`) 
                VALUES 
                    (1, 1, 'top5poster', 1, 0), 
                    (2, 1, 'top5poster', 2, 0), 
                    (3, 1, 'top5poster', 3, 0), 
                    (4, 1, 'top5poster', 4, 0), 
                    (5, 1, 'top5poster', 5, 0), 
                    (6, 1, 'topthreadstarter', 0, 0), 
                    (7, 1, 'mostsmileys', 0, 0), 
                    (8, 1, 'top5photo', 1, 0), 
                    (9, 1, 'top5photo', 2, 0), 
                    (10, 1, 'top5photo', 3, 0), 
                    (11, 1, 'top5photo', 4, 0), 
                    (12, 1, 'top5photo', 5, 0), 
                    (13, 1, 'topviewedphoto', 0, 0)";
        mysql_query($sql) or die(mysql_error());
        // create fcms_alerts
        $sql = "CREATE TABLE `fcms_alerts` (
                    `id` INT(25) NOT NULL AUTO_INCREMENT, 
                    `alert` VARCHAR(50) NOT NULL DEFAULT '0', 
                    `user` INT(25) NOT NULL DEFAULT '0', 
                    `hide` TINYINT(1) NOT NULL DEFAULT '1',
                    PRIMARY KEY (`id`),
                    KEY `alert_ind` (`alert`),
                    KEY `user_ind` (`user`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        mysql_query($sql) or die(mysql_error());
        echo '
        <div id="install">
            <h1>'._('Hooray!  Yippie!').'</h1>
            <p>'.sprintf(_('%s has been installed successfully.'), 'Family Connections').'</p>
            <p><a href="index.php">'.sprintf(_('Please continue to the homepage to login and begin using %s.'), 'Family Connections').'</a><p>
        </div>';
    }
} ?>
