<?php
/**
 * Family Connections - www.familycms.com
 * 
 * PHP versions 4 and 5
 * 
 * Copyright (C) 2007 Ryan Haudenschilt
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */

require_once 'inc/gettext.inc';
require_once 'inc/constants.php';

// Setup php-gettext
T_setlocale(LC_MESSAGES, 'en_US');
T_bindtextdomain('messages', './language');
T_bind_textdomain_codeset('messages', 'UTF-8');
T_textdomain('messages');

// Fix magic quotes
if (get_magic_quotes_gpc())
{
    $_REQUEST = array_map('stripslashes', $_REQUEST);
    $_GET     = array_map('stripslashes', $_GET);
    $_POST    = array_map('stripslashes', $_POST);
    $_COOKIE  = array_map('stripslashes', $_COOKIE);
}

main();
return;

/**
 * main 
 * 
 * @return void
 */
function main ()
{
    displayHeader();

    if (isset($_POST['submit1']))
    {
        displayStepTwo();
    }
    elseif (isset($_POST['submit2']))
    {
        displayStepThree();
    }
    elseif (isset($_POST['submit3']))
    {
        displayStepFour();
    }
    elseif (isset($_POST['submit4']))
    {
        displayStepFive();
    }
    elseif (isset($_POST['submit5']))
    {
        setupDatabase();
    }
    elseif (file_exists('inc/config_inc.php'))
    {
        displayAlreadyInstalled();
        displayStepOne();
        displayFooter();
        return;
    }
    else
    {
        displayStepOne();
    }
}

/**
 * displayHeader 
 * 
 * @return void
 */
function displayHeader ()
{
    echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.T_('lang').'" lang="'.T_('lang').'">
<head>
<title>Family Connections '.T_('Installation').'</title>
<link rel="stylesheet" type="text/css" href="themes/fcms-core.css" />
<script type="text/javascript" src="inc/js/prototype.js"></script>
<script type="text/javascript" src="inc/js/livevalidation.js"></script>
<link rel="stylesheet" type="text/css" href="themes/datechooser.css"/>
<script type="text/javascript" src="inc/js/datechooser.js"></script>
<script type="text/javascript">
//<![CDATA[
Event.observe(window, "load", function() {
    // Datechooser
    var objDatePicker = new DateChooser();
    objDatePicker.setUpdateField({"day":"j", "month":"n", "year":"Y"});
    objDatePicker.setIcon("themes/default/images/datepicker.jpg", "year");
    return true;
});
//]]>
</script>
</head>
<body>';
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter ()
{
    echo '
</body>
</html>';

}

/**
 * displayAlreadyInstalled 
 * 
 * If config file exists and where not in the middle of installing, error out
 * 
 * @return void
 */
function displayAlreadyInstalled ()
{
    echo '
    <div id="install">
        <h1>'.T_('Wait a Minute.').'</h1>
        <p>'.T_('The installation for this site is already finished. Running the installation again will cause you to Lose All Your Previous Information.  Are you sure you want to continue?').'</p>
        <div class="clearfix">
            <div class="option">
                <a class="nbtn" href="index.php">'.T_('No').'</a><br/><br/>
                '.T_('I just want to view my site.').'
            </div>
            <div class="option">
                <a class="ybtn" href="#" onclick="$(\'show-install\').toggle(); 
                    $(\'install\').toggle(); document.setupform.dbhost.focus(); return false">'.T_('Yes').'</a><br/><br/>
                '.T_('I want to run the installation anyway. (Not Recommended)').'
            </div>
        </div>
        <p>&nbsp;</p>
        <br/>
    </div>
    <div id="show-install" style="display:none;">';
}

/**
 * displayStepOne 
 * 
 * @return void
 */
function displayStepOne ()
{
    $inc    = "<span class=\"bad\">".T_('BAD')."</span>";
    $avatar = "<span class=\"bad\">".T_('BAD')."</span>";
    $docs   = "<span class=\"bad\">".T_('BAD')."</span>";
    $photos = "<span class=\"bad\">".T_('BAD')."</span>";
    $up     = "<span class=\"bad\">".T_('BAD')."</span>";
    $curl   = "<span class=\"bad\">".T_('BAD')."</span>";
    $php    = "<span class=\"bad\">".T_('BAD')."</span>";

    // Check inc
    $check_inc = false;
    if (isWritable('inc/'))
    {
        $check_inc = true;
        $inc       = "<span class=\"ok\">".T_('OK')."</span>";
    }

    // Check avatar
    $check_avatar = false;
    if (isWritable('uploads/avatar/'))
    {
        $check_avatar = true;
        $avatar       = "<span class=\"ok\">".T_('OK')."</span>";
    }

    // Check documents
    $check_docs = false;
    if (isWritable('uploads/documents/'))
    {
        $check_docs = true;
        $docs       = "<span class=\"ok\">".T_('OK')."</span>";
    }

    // Check photos
    $check_photos = false;
    if (isWritable('uploads/photos/'))
    {
        $check_photos = true;
        $photos       = "<span class=\"ok\">".T_('OK')."</span>";
    }

    // Check upimages
    $check_up = false;
    if (isWritable('uploads/upimages/'))
    {
        $check_up = true;
        $up       = "<span class=\"ok\">".T_('OK')."</span>";
    }

    // Check curl support
    $check_curl = false;
    if (function_exists('curl_init'))
    {
        $check_curl = true;
        $curl       = "<span class=\"ok\">".T_('OK')."</span>";
    }

    // Check PHP Version
    $check_php = false;
    if (function_exists('version_compare') && version_compare(phpversion(), '5.0.0', '>='))
    {
        $check_php = true;
        $php       = "<span class=\"ok\">".T_('OK')."</span>";
    }

    echo '
    <div id="column">
        <h2>'.T_('Pre-Installation Check').'</h2>
        <form class="nofields" action="install.php" method="post">
            <div style="text-align:center">'.T_('Step 1 of 5').'</div><div class="progress"><div style="width:20%"></div></div>
            <div><b>'.T_('Checking Requirements').'</b></div>
            <div><div class="dir">PHP 5+</div> <div class="status">'.$php.'</div></div>
            <div style="clear:both;"></div>
            <div><div class="dir">cURL</div> <div class="status">'.$curl.'</div></div>
            <div style="clear:both;"></div>
            <p>&nbsp;</p>
            <div><b>'.T_('Checking Folder Permissions').'</b></div>
            <div><div class="dir">inc/</div> <div class="status">'.$inc.'</div></div>
            <div style="clear:both;"></div>
            <div><div class="dir">uploads/avatar/</div> <div class="status">'.$avatar.'</div></div>
            <div style="clear:both;"></div>
            <div><div class="dir">uploads/documents/</div> <div class="status">'.$docs.'</div></div>
            <div style="clear:both;"></div>
            <div><div class="dir">uploads/photos/</div> <div class="status">'.$photos.'</div></div>
            <div style="clear:both;"></div>
            <div><div class="dir">uploads/upimages/</div> <div class="status">'.$up.'</div></div>
            <div style="clear:both;"></div>';

    if ($check_inc && $check_avatar && $check_docs && $check_photos && $check_up && $check_curl && $check_php)
    {
        echo '
            <p>'.T_('Your site is ready to be installed.  Please proceed to the next step.').'</p>
            <p style="text-align:right;"><input id="submit" name="submit1" type="submit"  value="'.T_('Next').' >>"/></p>
            <div class="clear"></div>';
    }
    else
    {
        echo '
            <br/><div>'.T_('Unfortunatly your site is not ready to be installed.  Please make sure that the folders above exist and have the proper permissions set.').'</div>';
    }

    echo '
        </form>
    </div><!-- /column -->';
}

/**
 * displayStepTwo 
 * 
 * @param string $error Any previous errors with this step.
 * 
 * @return void
 */
function displayStepTwo ($error = '0')
{
    echo '
    <div id="column">
        <h1>'.T_('Install').' Family Connections</h1>
        <h2>'.T_('Database Information').'</h2>
        <p style="text-align:center">'.T_('Step 2 of 5').'</p>
        <div class="progress"><div style="width:40%"></div></div>';

    if ($error !== '0')
    {
        echo $error;
    }

    echo '
        <form action="install.php" method="post">
        <div>
            <div class="field-label"><label for="dbhost"><b>'.T_('Database Host').'</b> <span class="req">*</span></label></div>
            <div class="field-widget">
                <input type="text" name="dbhost" id="dbhost"/>
                <div>'.T_('This is usually localhost or your database ip address.').'</div>
            </div>
        </div>
        <script type="text/javascript">
            var fdbhost = new LiveValidation(\'dbhost\', { onlyOnSubmit: true });
            fdbhost.add(Validate.Presence, {failureMessage: "'.T_('Sorry, but I can\'t install without this information.').'"});
        </script>
        <div>
            <div class="field-label"><label for="dbname"><b>'.T_('Database Name').'</b> <span class="req">*</span></label></div>
            <div class="field-widget">
                <input type="text" name="dbname" id="dbname"/>
                <div>'.sprintf(T_('The database name where you want to install %s.'), 'Family Connections').'</div>
            </div>
        </div>
        <script type="text/javascript">
            var fdbname = new LiveValidation(\'dbname\', { onlyOnSubmit: true });
            fdbname.add(Validate.Presence, {failureMessage: "'.T_('Sorry, but I can\'t install without this information.').'"});
        </script>
        <div>
            <div class="field-label"><label for="dbuser"><b>'.T_('Database Username').'</b> <span class="req">*</span></label></div>
            <div class="field-widget">
                <input type="text" name="dbuser" id="dbuser"/>
                <div>'.T_('The username for the database specified above.').'</div>
            </div>
        </div>
        <script type="text/javascript">
            var fdbuser = new LiveValidation(\'dbuser\', { onlyOnSubmit: true });
            fdbuser.add(Validate.Presence, {failureMessage: "'.T_('There has to be a user for this database.').'"});
        </script>     
        <div>
            <div class="field-label"><label for="dbpass"><b>'.T_('Database Password').'</b> <span class="req">*</span></label></div>
            <div class="field-widget">
                <input type="password" name="dbpass" id="dbpass"/>
                <div>'.T_('The password for the database specified above.').'</div>
            </div>
        </div>
        <script type="text/javascript">
            var fdbpass = new LiveValidation(\'dbpass\', { onlyOnSubmit: true });
            fdbpass.add(Validate.Presence, {failureMessage: "'.T_('Passwords are hard to remember, but unfortunately necessary.').'"});
        </script>
        <p style="text-align:right;"><input id="submit" name="submit2" type="submit"  value="'.T_('Next').' >>"/></p>
        <div class="clear"></div>
        </form>
    </div><!-- /column -->';
}

/**
 * displayStepThree 
 * 
 * @return void
 */
function displayStepThree ()
{
    // Check required fields
    $requiredFields  = array('dbhost', 'dbname', 'dbuser', 'dbpass');
    $missingRequired = false;

    foreach ($requiredFields as $field)
    {
        if (!isset($_POST[$field]))
        {
            $missingRequired = true;
        }
    }
    if ($missingRequired)
    {
        echo '
        <script type="text/javascript">
        Event.observe(window, \'load\', function() { $(\'dbhost\').focus(); });
        </script>';

        displayStepTwo("<p class=\"error\">".T_('You forgot a required field.  Please fill out all required fields.')."</p>");
        return;
    }

    $file = fopen('inc/config_inc.php', 'w') or die("<h1>Error Creating Config File</h1>");
    $str  = "<?php \$cfg_mysql_host = '".$_POST['dbhost']."'; \$cfg_mysql_db = '".$_POST['dbname']."'; \$cfg_mysql_user = '".$_POST['dbuser']."'; \$cfg_mysql_pass = '".$_POST['dbpass']."'; ?".">";

    fwrite($file, $str) or die("<h1>Could not write to config.</h1>");
    fclose($file);

    include_once 'inc/config_inc.php';
    include_once 'inc/install_inc.php';

    echo '
    <div id="column">
        <h1>'.T_('Install').'Family Connections</h1>
        <form class="nofields" action="install.php" method="post">
        <h2>'.T_('Checking Database Connection').'</h2>
        <p style="text-align:center">'.T_('Step 3 of 5').'</p>
        <div class="progress"><div style="width:60%"></div></div>
        <div>
            '.sprintf(T_('Attempting to connect to database %s on %s using user %s.'), "<i>$cfg_mysql_db</i>", "<i>$cfg_mysql_host</i>", "<i>$cfg_mysql_user</i>").'
        </div>';

    $connection = @mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);

    if (!$connection)
    {
        die('<h3 class="bad">'.T_('Uh-Oh!').'</h3><div>'.T_('A connection to the database could not be made.  Please shut down your browser and then re-run the installation.').'</div>');
    }

    mysql_select_db($cfg_mysql_db) or die("<h1>Error</h1><p><b>Connection made, but database could not be found!</b></p>".mysql_error());

    dropTables();

    echo '
        <h3>'.T_('Awesome!').'</h3>
        <div>'.T_('A connection was successfully made to the database.  Please proceed to the next step.').'</div>
        <p style="text-align:right;"><input id="submit" name="submit3" type="submit"  value="'.T_('Next').' >>"/></p>
        <div class="clear"></div>
        </form>
    </div><!-- /column -->';
}

/**
 * displayStepFour 
 * 
 * @param string $error Any previous errors with this step.
 * 
 * @return void
 */
function displayStepFour ($error = '0')
{
    echo '
    <script type="text/javascript">Event.observe(window, \'load\', function() { $(\'sitename\').focus(); });</script>
    <div id="column">
        <h1>'.T_('Install').' Family Connections</h1>
        <h2>'.T_('Website Information').'</h2>
        <p style="text-align:center">'.T_('Step 4 of 5').'</p>
        <div class="progress"><div style="width:80%"></div></div>
        <form action="install.php" method="post">
            <div>
                <div class="field-label"><label for="sitename"><b>'.T_('Website Name').'</b> <span class="req">*</span></label>
                <div class="field-widget big">
                    <input type="text" name="sitename" id="sitename" title="'.T_('What do you want your website to be called?').'"/>
                    <div>'.T_('(Examples: "The Smith\'s" or "The Johnson Family Website")').'</div>
                </div>
            </div>
            <script type="text/javascript">
                var fsitename = new LiveValidation(\'sitename\', { onlyOnSubmit: true });
                fsitename.add(Validate.Presence, {failureMessage: "'.T_('Without a name, how will you tell people about your website?').'"});
            </script>
            <div>
                <div class="field-label"><label for="contact"><b>'.T_('Contact Email').'</b> <span class="req">*</span></label></div>
                <div class="field-widget big">
                    <input type="text" name="contact" id="contact"/>
                    <div>'.T_('The email address you want all questions, comments and concerns about the site to go.').'</div>
                </div>
            </div>
            <script type="text/javascript">
                var fcontact = new LiveValidation(\'contact\', { onlyOnSubmit: true });
                fcontact.add( Validate.Presence, { failureMessage: "'.T_('Sorry, but this information is required.').'" } );
                fcontact.add( Validate.Email, { failureMessage: "'.T_('That\'s not a valid emaill address is it?').'" } );
                fcontact.add( Validate.Length, { minimum: 10 } );
            </script>
            <div>
                <div class="field-label"><label><b>'.T_('Optional Sections').'</b></label></div>
                <div>
                    <input type="checkbox" name="sections-news" id="sections-news" value="familynews"/>
                    <label for="sections-news">'.T_('Family News').'</label><br/>
                    <input type="checkbox" name="sections-recipes" id="sections-recipes" value="recipes"/>
                    <label for="sections-recipes">'.T_('Recipes').'</label><br/>
                    <input type="checkbox" name="sections-tree" id="sections-tree" value="tree"/>
                    <label for="sections-tree">'.T_('Family Tree').'</label><br/>
                    <input type="checkbox" name="sections-documents" id="sections-documents" value="documents"/>
                    <label for="sections-documents">'.T_('Documents').'</label><br/>
                    <input type="checkbox" name="sections-prayers" id="sections-prayers" value="prayerconcerns"/>
                    <label for="sections-prayers">'.T_('Prayer Concerns').'</label><br/>
                    <input type="checkbox" name="sections-whereiseveryone" id="sections-whereiseveryone" value="whereiseveryone"/>
                    <label for="sections-whereiseveryone">'.T_('Where Is Everyone').'</label>
                </div>
            </div>
            <p>'.T_('Which sections would you like to use on your site?').'</p>
            <p style="text-align:right;"><input id="submit" name="submit4" type="submit"  value="'.T_('Next').' >>"/></p>
            <div class="clear"></div>
        </form>
    </div><!-- /column -->';
}

/**
 * displayStepFive 
 * 
 * @param string $error Any previous errors with this step.
 * 
 * @return void
 */
function displayStepFive ($error = '0')
{
    global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

    if (empty($_POST['sitename']) || empty($_POST['contact']))
    {
        displayStepFour("<p class=\"error\">".T_('You forgot a required field.  Please fill out all required fields.')."</p>");
        return;
    }

    include_once 'inc/config_inc.php';
    include_once 'inc/install_inc.php';
    include_once 'inc/datetime.php';

    mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);
    mysql_select_db($cfg_mysql_db);

    if (version_compare(phpversion(), "4.3.0") == "-1")
    {
        $_POST['sitename'] = mysql_escape_string($_POST['sitename']);
        $_POST['contact']  = mysql_escape_string($_POST['contact']);
    }
    else
    {
        $_POST['sitename'] = mysql_real_escape_string($_POST['sitename']);
        $_POST['contact']  = mysql_real_escape_string($_POST['contact']);
    }

    // Setup Config
    installConfig($_POST['sitename'], $_POST['contact'], 'Family Connections 2.7.1');

    // Setup Navigation
    $order  = 0;
    $order2 = 0;

    $nextComOrder   = 2;
    $nextShareOrder = 5;
    $nextAdminOrder = 10;

    $params = array();

    // Family News
    if (isset($_POST['sections-news']))
    {
        $order = $nextComOrder;
        $nextComOrder++;
    }
    $params['familynews'] = array(3, $order, 0);

    // Prayers
    $order = 0;
    if (isset($_POST['sections-prayers']))
    {
        $order = $nextComOrder;
        $nextComOrder++;
    }
    $params['prayers'] = array(3, $order, 0);

    // Recipes
    $order = 0;
    if (isset($_POST['sections-recipes']))
    {
        $order = $nextShareOrder;
        $nextShareOrder++;
    }
    $params['recipes'] = array(4, $order, 0);

    // Family Tree
    $order = 0;
    if (isset($_POST['sections-tree']))
    {
        $order = $nextShareOrder;
        $nextShareOrder++;
    }
    $params['tree'] = array(4, $order, 0);

    // Documents
    $order = 0;
    if (isset($_POST['sections-documents']))
    {
        $order = $nextShareOrder;
        $nextShareOrder++;
    }
    $params['documents'] = array(4, $order, 0);

    // Where Is Everyone
    $order = 0;
    if (isset($_POST['sections-whereiseveryone']))
    {
        $order  = $nextShareOrder;
        $order2 = $nextAdminOrder;
        $nextShareOrder++;
        $nextAdminOrder++;

        $params['admin_foursquare'] = array(6, $order2, 0);
    }
    $params['whereiseveryone'] = array(4, $order, 0);

    installNavigation($params);

    echo '
    <div id="column">
        <h1>'.T_('Install').' Family Connections</h1>
        <h2>'.T_('Administrative Account').'</h2>
        <p style="text-align:center">'.T_('Step 5 of 5').'</p>
        <div class="progress"><div style="width:100%"></div></div>
        <form action="install.php" method="post">
            <p>'.T_('Everyone will be required to have an account and be logged in at all times to use this website.  This will help protect your site.').'</p>
            <p>'.T_('You must have at least one administrative account.  Please fill out the information below for the person who will be the administrator of this site.').'</p>
            <div>
                <div class="field-label"><label for="username"><b>'.T_('Username').'</b> <span class="req">*</span></label></div>
                <div class="field-widget"><input type="text" name="username" id="username"/></div>
            </div>
            <script type="text/javascript">
                var funame = new LiveValidation(\'username\', { onlyOnSubmit: true });
                funame.add(Validate.Presence, {failureMessage: "'.T_('Sorry, but I can\'t install without this information.').'"});
            </script>
            <div>
                <div class="field-label"><label for="password"><b>'.T_('Password').'</b> <span class="req">*</span></label></div>
                <div class="field-widget"><input type="password" name="password" id="password"/></div>
            </div>
            <script type="text/javascript">
                var fpass = new LiveValidation(\'password\', { onlyOnSubmit: true });
                fpass.add(Validate.Presence, {failureMessage: "'.T_('Passwords are hard to remember, but unfortunately necessary.').'"});
            </script>
            <div>
                <div class="field-label"><label for="fname"><b>'.T_('First Name').'</b> <span class="req">*</span></label></div>
                <div class="field-widget"><input type="text" name="fname" id="fname"/></div>
            </div>
            <script type="text/javascript">
                var ffname = new LiveValidation(\'fname\', { onlyOnSubmit: true });
                ffname.add(Validate.Presence, {failureMessage: "'.T_('Sorry, but I can\'t install without this information.').'"});
            </script>
            <div>
                <div class="field-label"><label for="lname"><b>'.T_('Last Name').'</b> <span class="req">*</span></label></div>
                <div class="field-widget"><input type="text" name="lname" id="lname"/></div>
            </div>
            <script type="text/javascript">
                var flname = new LiveValidation(\'lname\', { onlyOnSubmit: true });
                flname.add(Validate.Presence, {failureMessage: "'.T_('Sorry, but I can\'t install without this information.').'"});
            </script>
            <div>
                <div class="field-label"><label for="email"><b>'.T_('Email Address').'</b> <span class="req">*</span></label></div>
                <div class="field-widget"><input type="text" name="email" id="email"/></div>
            </div>
            <script type="text/javascript">
                var femail = new LiveValidation(\'email\', { onlyOnSubmit: true });
                femail.add( Validate.Presence, { failureMessage: "'.T_('Sorry, but this information is required.').'" } );
                femail.add( Validate.Email, { failureMessage: "'.T_('That\'s not a valid email address is it?').'" } );
                femail.add( Validate.Length, { minimum: 10 } );
            </script>
            <div>
                <div class="field-label"><label for="day"><b>'.T_('Birthday').'</b><span class="req">*</span></label></div>
                <div class="field-widget">
                    <select id="day" name="day">';

    $d = 1;
    while ($d <= 31)
    {
        echo "<option value=\"$d\">$d</option>";
        $d++;
    }
    echo '</select><select id="month" name="month">';
    $m = 1;
    while ($m <= 12)
    {
        echo "<option value=\"$m\">".getMonthAbbr($m)."</option>";
        $m++;
    }
    echo '</select><select id="year" name="year">';
    $y = 1900;
    while ($y - 5 <= gmdate('Y'))
    {
        echo "<option value=\"$y\">$y</option>";
        $y++;
    }

    echo '
                    </select>
                </div>
            </div>
            <p style="text-align:right;"><input id="submit" name="submit5" type="submit"  value="'.T_('Next').' >>"/></p>
            <div class="clear"></div>
        </form>
    </div><!-- /column -->';
}

/**
 * setupDatabase 
 * 
 * @return void
 */
function setupDatabase ()
{
    if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['fname']) || empty($_POST['lname']) || empty($_POST['email']))
    {
        displayStepFive("<p class=\"error\">".T_('You forgot a required field.  Please fill out all required fields.')."</p>");
        return;
    }

    include_once 'inc/config_inc.php';
    include_once 'inc/install_inc.php';
    include_once 'inc/utils.php';

    $password   = md5($_POST['password']);
    $connection = mysql_connect($cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass);

    if (!$connection)
    {
        die("<h1>Connection Error [".__FILE__.__LINE__."]</h1>".mysql_error());
    }

    mysql_select_db($cfg_mysql_db) or die("<h1>Error</h1><p><b>Database could not be found!</b></p>".mysql_error());

    $fname    = cleanInput($_POST['fname']);
    $lname    = cleanInput($_POST['lname']);
    $email    = cleanInput($_POST['email']);
    $bYear    = cleanInput($_POST['year']);
    $bMonth   = cleanInput(str_pad($_POST['month'], 2, "0", STR_PAD_LEFT));
    $bDay     = cleanInput(str_pad($_POST['day'], 2, "0", STR_PAD_LEFT));
    $username = cleanInput($_POST['username']);

    installUsers($fname, $lname, $email, $bYear, $bMonth, $bDay, $username, $password);
    installCategory();
    installCalendar();
    installTables();

    echo '
    <div id="install">
        <h1>'.T_('Hooray!  Yippie!').'</h1>
        <p>'.sprintf(T_('%s has been installed successfully.'), 'Family Connections').'</p>
        <p><a href="index.php">'.sprintf(T_('Please continue to the homepage to login and begin using %s.'), 'Family Connections').'</a><p>
    </div>';
}

/**
 * isWritable 
 * 
 * will work in despite of Windows ACLs bug
 *
 * NOTE: use a trailing slash for folders!!!
 * see http://bugs.php.net/bug.php?id=27609
 * see http://bugs.php.net/bug.php?id=30931
 * 
 * @param string $path File path to check permissions
 * 
 * @return  void
 */
function isWritable ($path)
{
    if ($path{strlen($path)-1}=='/') // recursively return a temporary file path
        return isWritable($path.uniqid(mt_rand()).'.tmp');
    else if (@is_dir($path))
        return isWritable($path.'/'.uniqid(mt_rand()).'.tmp');
    // check tmp file for read/write capabilities
    $rm = file_exists($path);
    $f  = @fopen($path, 'a');
    if ($f===false)
        return false;
    fclose($f);
    if (!$rm)
        unlink($path);
    return true;
}

