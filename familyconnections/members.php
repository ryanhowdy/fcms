<?php
/**
 * Members.
 *
 * PHP versions 4 and 5
 *
 * @category  FCMS
 *
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * @copyright 2008 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
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
     * Constructor.
     *
     * @return void
     */
    public function __construct($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError = $fcmsError;
        $this->fcmsDatabase = $fcmsDatabase;
        $this->fcmsUser = $fcmsUser;

        $this->control();
    }

    /**
     * control.
     *
     * The controlling structure for this script.
     *
     * @return void
     */
    public function control()
    {
        $this->displayMembers();
    }

    /**
     * displayHeader.
     *
     * @return void
     */
    public function displayHeader()
    {
        $params = [
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Members'),
            'pageId'        => 'members',
            'path'          => URL_PREFIX,
            'displayname'   => $this->fcmsUser->displayName,
            'version'       => getCurrentVersion(),
            'year'          => date('Y'),
        ];

        displayPageHeader($params);

        $order = isset($_GET['order']) ? $_GET['order'] : 'alphabetical';

        $alpha = $age = $part = $act = $join = '';
        if ($order == 'alphabetical')
        {
            $alpha = 'class="selected"';
        }
        elseif ($order == 'age')
        {
            $age = 'class="selected"';
        }
        elseif ($order == 'participation')
        {
            $part = 'class="selected"';
        }
        elseif ($order == 'activity')
        {
            $act = 'class="selected"';
        }
        elseif ($order == 'joined')
        {
            $join = 'class="selected"';
        }

        echo '
            <div id="leftcolumn">
                <h3>'.T_('Views').'</h3>
                <ul>
                    <li '.$alpha.'><a href="?order=alphabetical">'.T_('Alphabetical').'</a></li>
                    <li '.$age.'><a href="?order=age">'.T_('Age').'</a></li>
                    <li '.$part.'><a href="?order=participation">'.T_('Participation').'</a></li>
                    <li '.$act.'><a href="?order=activity">'.T_('Last Seen').'</a></li>
                    <li '.$join.'><a href="?order=joined">'.T_('Joined').'</a></li>
                </ul>
            </div>
            <div id="maincolumn">';
    }

    /**
     * displayFooter.
     *
     * @return void
     */
    public function displayFooter()
    {
        $params = [
            'path'      => URL_PREFIX,
            'version'   => getCurrentVersion(),
            'year'      => date('Y'),
        ];

        echo '
            </div><!--/#maincolumn-->';

        loadTemplate('global', 'footer', $params);
    }

    /**
     * displayMembers.
     *
     * @return void
     */
    public function displayMembers()
    {
        $this->displayHeader();

        $order = isset($_GET['order']) ? $_GET['order'] : 'alphabetical';

        $tzOffset = getTimezone($this->fcmsUser->id);

        $validOrderTypes = [
            'alphabetical'  => 'ORDER BY u.`fname`',
            'age'           => 'ORDER BY u.`dob_year`, u.`dob_month`, u.`dob_day`',
            'participation' => '',
            'activity'      => 'ORDER BY u.`activity` DESC',
            'joined'        => 'ORDER BY u.`joindate` DESC',
        ];

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
                WHERE u.`phpass` != 'NONMEMBER'
                AND u.`phpass` != 'PRIVATE'
                OR (
                    u.`phpass` IS NULL
                    AND u.`password` != 'NONMEMBER'
                    AND u.`password` != 'PRIVATE'
                )
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

        // Get Additional header columns
        $header = '';
        $colspan = 4;

        if ($order == 'age')
        {
            $header = '<td>'.T_('Age').'</td><td>'.T_('Birthday').'</td>';
            $colspan++;
        }
        elseif ($order == 'participation')
        {
            $header = '<td>'.T_('Participation Points').'</td>';
        }
        elseif ($order == 'activity')
        {
            $header = '<td>'.T_('Last Seen').'</td>';
        }
        elseif ($order == 'joined')
        {
            $header = '<td>'.T_('Joined').'</td>';
        }

        echo '
        <table cellspacing="0" cellpadding="0">
            <thead>
                <th colspan="'.$colspan.'"></th>
            </thead>
            <tbody>
                <tr class="header">
                    <td></td>
                    <td>'.T_('Name').'</td>
                    <td>'.T_('Username').'</td>
                    '.$header.'
                </tr>';

        foreach ($memberData as $row)
        {
            $display = '';

            // Age
            if ($order == 'age')
            {
                $age = getAge($row['dob_year'], $row['dob_month'], $row['dob_day']);

                // Don't show users with an unknown age
                if ($age === '...')
                {
                    continue;
                }

                $display = '<td>'.sprintf(T_('%s years old'), $age).'</td>';
                $display .= '<td>'.$row['dob_year'].'-'.$row['dob_month'].'-'.$row['dob_day'].'</td>';
            }
            // Participation
            elseif ($order == 'participation')
            {
                $display = '<td>'.$row['points'].'</td>';
            }
            // Last Seen
            elseif ($order == 'activity')
            {
                $display = '<td></td>';

                if ($row['activity'] != '0000-00-00 00:00:00')
                {
                    $display = '<td>'.fixDate(T_('M. j, Y (g:i a)'), $tzOffset, $row['activity']).'</td>';
                }
            }
            // Joined
            elseif ($order == 'joined')
            {
                $display = '<td>'.fixDate(T_('M. j, Y'), $tzOffset, $row['joindate']).'</td>';
            }

            // Display members
            echo '
                <tr>
                    <td>
                        <a class="avatar" href="profile.php?member='.(int) $row['id'].'">
                            <img alt="avatar" src="'.getCurrentAvatar($row['id']).'"/>
                        </a>
                    </td>
                    <td>
                        <a class="avatar" href="profile.php?member='.(int) $row['id'].'">
                            '.cleanOutput($row['fname']).' '.cleanOutput($row['lname']).'
                        </a>
                    </td>
                    <td>'.cleanOutput($row['username']).'</td>
                    '.$display.'
                </tr>';
        }

        echo '
            </tbody>
        </table>';

        $this->displayFooter();
    }
}
