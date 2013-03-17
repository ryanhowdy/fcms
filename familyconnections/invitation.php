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
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load('datetime', 'calendar');

$calendar   = new Calendar($fcmsError, $fcmsDatabase, $fcmsUser);
$page       = new Page($fcmsError, $fcmsDatabase, $fcmsUser, $calendar);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsCalendar;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser, $fcmsCalendar)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;
        $this->fcmsCalendar = $fcmsCalendar;

        $this->control();
    }

    /**
     * control 
     * 
     * @return void
     */
    function control ()
    {
        if (!isset($_GET['code']) or !isset($_GET['event']))
        {
            $this->displayHeader();
            echo '<p><b>'.T_('Invalid URL').'</b></p>';
            $this->displayFooter();
            exit();
        }

        if (isset($_POST['attend_submit']))
        {
            $this->displayAttendSubmit();
        }
        else
        {
            $this->displayAttendForm();
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
<script type="text/javascript" src="ui/js/prototype.js"></script>
<link rel="stylesheet" type="text/css" href="ui/fcms-core.css" />
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initAttendingEvent();
});
//]]>
</script>
</head>
<body id="invitation" class="clearfix">
    <img id="logo" src="ui/images/logo.gif" alt="'.getSiteName().'"/>';
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
        $this->displayHeader();

        $id   = (int)$_GET['event'];
        $code = $_GET['code'];

        $sql = "SELECT `id`, `event_id`, `user`, `created`, `updated`, `attending`, `code`, `response`
                FROM `fcms_invitation` 
                WHERE `event_id` = ?
                AND `code` = ?";

        $params = array($id, $code);

        $invitation = $this->fcmsDatabase->getRow($sql, $params);
        if ($invitation === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (count($invitation) <= 0)
        {
            echo '<p><b>'.T_('Invalid Invitation Code!').'</b></p>';
            $this->displayFooter();

            return;
        }

        $sql = "SELECT c.`id`, c.`date`, c.`time_start`, c.`time_end`, c.`date_added`, 
                    c.`title`, c.`desc`, c.`created_by`, cat.`name` AS category, c.`repeat`, c.`private`
                FROM `fcms_calendar` AS c, `fcms_category` AS cat 
                WHERE c.`id` = ?
                    AND c.`category` = cat.`id` 
                LIMIT 1";

        $event = $this->fcmsDatabase->getRow($sql, $id);
        if ($event === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        if (count($event) <= 0)
        {
            echo '<p><b>'.T_('Invalid Event!').'</b></p>';
            $this->displayFooter();

            return;
        }

        $times = $this->fcmsCalendar->getTimesList();
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
                    <img src="ui/images/attend_yes.png"/><br/>
                    <b>'.T_('Yes').'</b>
                </label>
                <input type="radio" id="yes" name="attending" value="1"/>
            </li>
            <li>
                <label for="maybe">
                    <img src="ui/images/attend_maybe.png"/><br/>
                    <b>'.T_('Maybe').'</b>
                </label>
                <input type="radio" id="maybe" name="attending" value="2"/>
            </li>
            <li>
                <label for="no">
                    <img src="ui/images/attend_no.png"/><br/>
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

        $rows = $this->fcmsDatabase->getRows($sql, $id);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $yesCount       = 0;
        $noCount        = 0;
        $maybeCount     = 0;
        $undecidedCount = 0;
        $responses      = array();

        foreach ($rows as $r)
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
                $img = '<img src="ui/images/attend_no.png" alt="'.T_('No').'"/>';
            }
            elseif ($r['attending'] == 1)
            {
                $yesCount++;
                $img = '<img src="ui/images/attend_yes.png" alt="'.T_('Yes').'"/>';
            }
            elseif ($r['attending'] > 1)
            {
                $maybeCount++;
                $img = '<img src="ui/images/attend_maybe.png" alt="'.T_('Maybe').'"/>';
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
        <h3 class="coming"><img src="ui/themes/default/images/ok.gif"> '.T_('Yes').' ('.$yesCount.')</h3>
        <h3 class="coming"><img src="ui/themes/default/images/help.gif"> '.T_('Maybe').' ('.$maybeCount.')</h3>
        <h3 class="coming"><img src="ui/themes/default/images/delete.gif"> '.T_('No').' ('.$noCount.')</h3>
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

        $this->displayFooter();
    }

    /**
     * displayAttendSubmit 
     * 
     * @return void
     */
    function displayAttendSubmit ()
    {
        $attending = isset($_POST['attending']) ? (int)$_POST['attending'] : "NULL";

        $sql = "UPDATE `fcms_invitation`
                SET `response`  = ?,
                    `attending` = ?
                WHERE `id` = ?";

        $params = array(
            $_POST['response'],
            $attending,
            $_POST['id']
        );

        if (!$this->fcmsDatabase->update($sql, $params))
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        $this->displayAttendForm();
    }
}
