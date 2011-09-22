<?php
/**
 * Invitation
 *  
 * PHP versions 4 and 5
 *  
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2008 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     2.4
 */

define('URL_PREFIX', '');

require 'fcms.php';

load('datetime', 'calendar');

$calendar = new Calendar(1);

main();
exit();


/**
 * main 
 * 
 * @return void
 */
function main ()
{
    if (!isset($_GET['code']) or !isset($_GET['event']))
    {
        echo '<p><b>'.T_('Invalid URL').'</b></p>';
        displayFooter();
        exit();
    }

    if (isset($_POST['attend_submit']))
    {
        displayAttendSubmit();
    }
    else
    {
        displayAttendForm();
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
<title>'.getSiteName().' - '.T_('powered by').' '.getCurrentVersion().'</title>
<script type="text/javascript" src="inc/js/prototype.js"></script>
<link rel="stylesheet" type="text/css" href="themes/fcms-core.css" />
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initAttendingEvent();
});
//]]>
</script>
</head>
<body id="invitation" class="clearfix">
    <img id="logo" src="themes/images/logo.gif" alt="'.getSiteName().'"/>';
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
 * displayAttendForm 
 * 
 * @return void
 */
function displayAttendForm ()
{
    global $calendar;

    displayHeader();

    $code = cleanInput($_GET['code']);
    $id   = cleanInput($_GET['event'], 'int');

    $sql = "SELECT `id`, `event_id`, `user`, `created`, `updated`, `attending`, `code`, `response`
            FROM `fcms_invitation` 
            WHERE `event_id` = '$id'
            AND `code` = '$code'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Code Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        exit();
    }

    $invitation = mysql_fetch_array($result);
    if (!$invitation)
    {
        echo '
        <p><b>'.T_('Invalid Invitation Code!').'</b></p>';
        displayFooter();
        exit();
    }

    $sql = "SELECT c.`id`, c.`date`, c.`time_start`, c.`time_end`, c.`date_added`, 
                c.`title`, c.`desc`, c.`created_by`, cat.`name` AS category, c.`repeat`, c.`private`
            FROM `fcms_calendar` AS c, `fcms_category` AS cat 
            WHERE c.`id` = '$id' 
                AND c.`category` = cat.`id` 
            LIMIT 1";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Code Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        exit();
    }
    $event = mysql_fetch_array($result);

    if (!$event)
    {
        echo '
        <p><b>'.T_('Invalid Event!').'</b></p>';
        displayFooter();
        exit();
    }

    $times = $calendar->getTimesList();
    $date  = formatDate(T_('F j, Y'), $event['date']);
    $title = cleanOutput($event['title']);
    $host  = getUserDisplayname($event['created_by'], 2);

    $time = '';
    $cat  = '';
    $desc = '';

    list($year, $month, $day) = explode('-', $event['date']);

    // handle time
    if (isset($times[$event['time_start']]))
    {
        // one moment in time
        if ($event['time_start'] == $event['time_end'])
        {
            $time = '<br/>'.sprintf(T_('beginning at %s'), $times[$event['time_start']]);
        }
        // start and end
        else
        {
            $time = '<br/>'.sprintf(T_('between %s and %s'), $times[$event['time_start']], $times[$event['time_end']]);
        }
    }

    if (!empty($event['desc']))
    {
        $desc = '<br/>'.cleanOutput($event['desc']);
    }

    echo '
    <div id="event_details">
        <h1>'.$title.'</h1>
        <p id="desc">'.$desc.'</p>
        <div>
            <h2>'.T_('When').'</h2>
            <p><b>'.$date.'</b> '.$time.'</p>
            <h2>'.T_('Host').'</h2>
            <p>'.$host.'</p>
        </div>
    </div>';

    if ($invitation['attending'] === null)
    {
        echo '
    <form action="invitation.php?event='.$id.'&amp;code='.$code.'" method="post">
        <h1 id="attending_header">'.T_('Are you attending?').'</h1>
        <ul id="attending" class="clearfix">
            <li>
                <label for="yes">
                    <img src="themes/images/attend_yes.png"/><br/>
                    <b>'.T_('Yes').'</b>
                </label>
                <input type="radio" id="yes" name="attending" value="1"/>
            </li>
            <li>
                <label for="maybe">
                    <img src="themes/images/attend_maybe.png"/><br/>
                    <b>'.T_('Maybe').'</b>
                </label>
                <input type="radio" id="maybe" name="attending" value="2"/>
            </li>
            <li>
                <label for="no">
                    <img src="themes/images/attend_no.png"/><br/>
                    <b>'.T_('No').'</b>
                </label>
                <input type="radio" id="no" name="attending" value="0"/>
            </li>
            <li class="submit">
                <textarea id="response" name="response" cols="50" rows="10"></textarea>
                <input type="hidden" id="id" name="id" value="'.$invitation['id'].'"/>
                <input type="submit" id="attend_submit" name="attend_submit" value="'.T_('Submit').'"/>
            </li>
        </ul>
    </form>';
    }

    // Get info on who's coming
    $sql = "SELECT `user`, `email`, `attending`, `response`, `updated`
            FROM `fcms_invitation`
            WHERE `event_id` = '$id'";

    $result = mysql_query($sql);
    if (!$result)
    {
        displaySQLError('Attending Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        exit();
    }

    $yesCount       = 0;
    $noCount        = 0;
    $maybeCount     = 0;
    $undecidedCount = 0;
    $responses      = array();

    while ($r = mysql_fetch_array($result))
    {
        $img = '';

        if ($r['attending'] === null)
        {
            $undecidedCount++;
            $img = T_('Undecided');
        }
        elseif ($r['attending'] == 0)
        {
            $noCount++;
            $img = '<img src="themes/images/attend_no.png" alt="'.T_('No').'"/>';
        }
        elseif ($r['attending'] == 1)
        {
            $yesCount++;
            $img = '<img src="themes/images/attend_yes.png" alt="'.T_('Yes').'"/>';
        }
        elseif ($r['attending'] > 1)
        {
            $maybeCount++;
            $img = '<img src="themes/images/attend_maybe.png" alt="'.T_('Maybe').'"/>';
        }

        $displayname = cleanOutput($r['email']);
        if ($r['user'] != 0)
        {
            $displayname = getUserDisplayName($r['user'], 2);
        }

        $responses[] = array(
            'user'        => $r['user'],
            'updated'     => $r['updated'],
            'displayname' => $displayname,
            'response'    => $r['response'],
            'attending'   => $r['attending'],
            'img'         => $img
        );
    }

    echo '
    <div id="leftcolumn">
        <h3>'.T_('Who\'s Coming').'</h3>
        <h3 class="coming"><img src="themes/default/images/ok.gif"> '.T_('Yes').' ('.$yesCount.')</h3>
        <h3 class="coming"><img src="themes/default/images/help.gif"> '.T_('Maybe').' ('.$maybeCount.')</h3>
        <h3 class="coming"><img src="themes/default/images/delete.gif"> '.T_('No').' ('.$noCount.')</h3>
        <h3 class="coming">'.T_('Undecided').' ('.$undecidedCount.')</h3>
    </div>

    <div id="maincolumn">';

    foreach ($responses as $response)
    {
        if (isset($response['attending']))
        {
            echo '
        <div class="comment_block clearfix">
            '.$response['img'].'
            <b>'.$response['displayname'].'</b> <i>'.$response['updated'].'</i>
            <p>
                '.cleanOutput($response['response']).'
            </p>
        </div>';
        }
    }

    echo '
    </div>';

    displayFooter();
}

/**
 * displayAttendSubmit 
 * 
 * @return void
 */
function displayAttendSubmit ()
{
    $id        = cleanInput($_POST['id'], 'int');
    $attending = isset($_POST['attending']) ? cleanInput($_POST['attending'], 'int') : "NULL";
    $response  = cleanInput($_POST['response']);

    $sql = "UPDATE `fcms_invitation`
            SET `response` = '$response',
                `attending` = $attending
            WHERE `id` = '$id'";
    if (!mysql_query($sql))
    {
        displaySQLError('Attending Error', __FILE__.' ['.__LINE__.']', $sql, mysql_error());
        displayFooter();
        exit();
    }

    displayAttendForm();
}
