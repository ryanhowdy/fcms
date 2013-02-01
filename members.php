<?php
/**
 * Members
 *  
 * PHP versions 4 and 5
 *  
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2008 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

define('URL_PREFIX', '');
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

load('members');

init();

$page = new Page($fcmsError, $fcmsDatabase, $fcmsUser);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;
    private $fcmsTemplate;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError    = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser     = $fcmsUser;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Members'),
            'path'          => URL_PREFIX,
            'displayname'   => $this->fcmsUser->displayName,
            'version'       => getCurrentVersion(),
            'year'          => date('Y')
        );

        $this->control();
    }

    /**
     * control 
     * 
     * The controlling structure for this script.
     * 
     * @return void
     */
    function control ()
    {
        $this->displayMembers();
    }

    /**
     * displayHeader 
     * 
     * @return void
     */
    function displayHeader ()
    {
        $TMPL = $this->fcmsTemplate;

        $TMPL['javascript'] = '
<script type="text/javascript">
Event.observe(window, "load", function() { initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\'); });
</script>';

        include_once getTheme($this->fcmsUser->id).'header.php';

        echo '
        <div id="members" class="centercontent">
            <div id="leftcolumn">
                <h3>'.T_('Order Members By:').'</h3>
                <ul class="menu">
                    <li><a href="?order=alphabetical">'.T_('Alphabetical').'</a></li>
                    <li><a href="?order=age">'.T_('Age').'</a></li>
                    <li><a href="?order=participation">'.T_('Participation').'</a></li>
                    <li><a href="?order=activity">'.T_('Last Seen').'</a></li>
                    <li><a href="?order=joined">'.T_('Joined').'</a></li>
                </ul>
            </div>
            <div id="maincolumn">';
    }

    /**
     * displayFooter 
     * 
     * @return void
     */
    function displayFooter ()
    {
        $TMPL = $this->fcmsTemplate;

        echo '
            </div><!-- /maincolumn -->
        </div><!-- /members -->';

        include_once getTheme($this->fcmsUser->id).'footer.php';
    }

    /**
     * displayMembers 
     * 
     * @return void
     */
    function displayMembers ()
    {
        $this->displayHeader();

        $order = isset($_GET['order']) ? $_GET['order'] : 'alphabetical';

        $tzOffset = getTimezone($this->fcmsUser->id);

        $validOrderTypes = array(
            'alphabetical'  => 'ORDER BY u.`fname`',
            'age'           => 'ORDER BY u.`dob_year`, u.`dob_month`, u.`dob_day`',
            'participation' => '',
            'activity'      => 'ORDER BY u.`activity` DESC',
            'joined'        => 'ORDER BY u.`joindate` DESC',
        );

        if (!array_key_exists($order, $validOrderTypes))
        {
            echo '
        <div class="error-alert">'.T_('Invalid Order.').'</div>';

            $this->displayFooter();
            return;
        }

        $sql = "SELECT u.`id`, u.`activity`, u.`joindate`, u.`fname`, u.`lname`, u.`sex`, 
                    u.`dob_year`, u.`dob_month`, u.`dob_day`, u.`username`, u.`avatar`, u.`gravatar`
                FROM `fcms_users` AS u
                WHERE u.`password` != 'NONMEMBER'
                AND u.`password` != 'PRIVATE'
                ".$validOrderTypes[$order];

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();

            return;
        }

        foreach ($rows as $row)
        {
            $row['points'] = getUserParticipationPoints($row['id']);

            $memberData[] = $row;
        }

        // Sort by participation
        if ($order == 'participation')
        {
            foreach ($memberData as $k => $v)
            {
                $b[$k] = strtolower($v['points']);
            }

            asort($b);

            foreach ($b as $key => $val)
            {
                $c[] = $memberData[$key];
            }

            $memberData = array_reverse($c);
        }

        echo '
        <ul id="memberlist">';

        foreach ($memberData AS $row)
        {
            $display = '';

            // Alphabetical
            if ($order == 'alphabetical')
            {
                $display = '('.$row['username'].')';
            }
            // Age
            elseif ($order == 'age')
            {
                $age = getAge($row['dob_year'], $row['dob_month'], $row['dob_day']);

                $display = sprintf(T_('%s years old'), $age);
            }
            // Participation
            elseif ($order == 'participation')
            {
                $display = $row['points'];
            }
            // Last Seen
            elseif ($order == 'activity')
            {
                $display = '';

                if ($row['activity'] != '0000-00-00 00:00:00')
                {
                    $display = fixDate(T_('M. j, Y'), $tzOffset, $row['activity']);
                }
            }
            // Joined
            elseif ($order == 'joined')
            {
                $display = fixDate(T_('M. j, Y'), $tzOffset, $row['joindate']);
            }

            // Display members
            echo '
                <li>
                    <a class="avatar" href="profile.php?member='.(int)$row['id'].'">
                        <img alt="avatar" src="'.getCurrentAvatar($row['id']).'"/>
                    </a><br/>
                    <a href="profile.php?member='.(int)$row['id'].'">'.cleanOutput($row['fname']).' '.cleanOutput($row['lname']).'</a><br/>
                    '.$display.'
                </li>';
        }

        echo '
            </ul>';

        $this->displayFooter();
    }
}
