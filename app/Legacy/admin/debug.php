<?php
/**
 * Debug
 * 
 * PHP version 5
 *
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2015 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
session_start();

define('URL_PREFIX', '../');
define('GALLERY_PREFIX', '../gallery/');

require URL_PREFIX.'fcms.php';

init('admin/');

// Globals
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
        $this->fcmsError        = $fcmsError;
        $this->fcmsDatabase     = $fcmsDatabase;
        $this->fcmsUser         = $fcmsUser;

        $this->fcmsTemplate = array(
            'sitename'      => getSiteName(),
            'nav-link'      => getAdminNavLinks(),
            'pagetitle'     => T_('Administration: Debug'),
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
        if ($this->fcmsUser->access > 1)
        {
            $this->displayInvalidAccessLevel();
            return;
        }
        else
        {
            $this->displayHeader();
            $this->displayDebug();
            $this->displayFooter();
        }
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
<script type="text/javascript" src="'.URL_PREFIX.'ui/js/jquery.js"></script>
<script type="text/javascript" src="'.URL_PREFIX.'ui/twitter-bootstrap/js/bootstrap-tabs.js"></script>';

        include_once URL_PREFIX.'ui/admin/header.php';

        echo '
        <div id="admin-debug">';
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
        </div><!-- /admin-debug -->';

        include_once URL_PREFIX.'ui/admin/footer.php';
    }

    /**
     * displayInvalidAccessLevel 
     * 
     * Display an error message for users who do not have admin access.
     * 
     * @return void
     */
    function displayInvalidAccessLevel ()
    {
        $this->displayHeader();

        echo '
            <p class="error-alert">
                <b>'.T_('You do not have access to view this page.').'</b><br/>
                '.T_('This page requires an access level 1 (Admin).').' 
                <a href="'.URL_PREFIX.'contact.php">'.T_('Please contact your website\'s administrator if you feel you should have access to this page.').'</a>
            </p>';

        $this->displayFooter();
    }

    /**
     * displayDebug
     * 
     * @return void
     */
    function displayDebug ()
    {
        global $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user;

        $this->displayHeader();

        // Get Config data
        $config = array();

        $sql = "SELECT `name`, `value`
                FROM `fcms_config`";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        foreach ($rows as $r)
        {
            $config[$r['name']] = $r['value'];
        }

        // Get Plugin data
        $disabledPlugins = array();

        $sql = "SELECT `id`, `link`, `col`, `order`, `req`
                FROM `fcms_navigation` 
                WHERE (`col` = 3 OR `col` = 4)
                AND `order` = 0";

        $rows = $this->fcmsDatabase->getRows($sql);
        if ($rows === false)
        {
            $this->fcmsError->displayError();
            $this->displayFooter();
            return;
        }

        foreach ($rows as $r)
        {
            $disabledPlugins[] = getPluginName($r['link']);
        }

        // Get MySQL attributes
        $mysqlVersion = $this->fcmsDatabase->getAttribute('PDO::ATTR_SERVER_VERSION');
        $mysqlClient  = $this->fcmsDatabase->getAttribute('PDO::ATTR_CLIENT_VERSION');

        echo '
            <ul class="tabs" data-tabs="tabs">
                <li class="active"><a href="#php">'.T_('PHP').'</a></li>
                <li><a href="#fcms">Family Connections</a></li>
                <li><a href="#database">'.T_('Database').'</a></li>
                <li><a href="#server">'.T_('Server').'</a></li>
            </ul>

            <div class="pill-content">
                <div class="active" id="php">
                    <table style="width:500px;" class="zebra-striped">
                        <thead>
                            <tr>
                                <th colspan="2">'.T_('PHP Information').'</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>'.T_('PHP Version').'</td>
                                <td>'.phpversion().'</td>
                            </tr>
                            <tr>
                                <td>'.T_('PHP Memory Usage').'</td>
                                <td>'.formatsize(memory_get_usage()).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('PHP Memory Limit').'</td>
                                <td>'.(ini_get('memory_limit') ? formatsize(ini_get('memory_limit')) : T_('N/A')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('PHP Max Upload Size').'</td>
                                <td>'.(ini_get('upload_max_filesize') ? formatsize(ini_get('upload_max_filesize')) : T_('N/A')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('PHP Max Post Size').'</td>
                                <td>'.(ini_get('post_max_size') ? formatsize(ini_get('post_max_size')) : T_('N/A')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('PHP Safe Mode').'</td>
                                <td>'.(ini_get('safe_mode') ? T_('On') : T_('Off')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('PHP Allow URL fopen').'</td>
                                <td>'.(ini_get('allow_url_fopen') ? T_('On') : T_('Off')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('PHP Allow URL Include').'</td>
                                <td>'.(ini_get('allow_url_include') ? T_('On') : T_('Off')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('PHP Display Errors').'</td>
                                <td>'.(ini_get('display_errors') ? T_('On') : T_('Off')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('PHP Display Startup Errors').'</td>
                                <td>'.(ini_get('display_startup_errors') ? T_('On') : T_('Off')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('PHP Expose PHP').'</td>
                                <td>'.(ini_get('expose_php') ? T_('On') : T_('Off')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('PHP Register Globals').'</td>
                                <td>'.(ini_get('register_globals') ? T_('On') : T_('Off')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('PHP Max Script Execution Time').'</td>
                                <td>'.(ini_get('max_execution_time') ? sprintf(T_('%s seconds'), ini_get('max_execution_time')) : T_('N/A')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('PHP Magic Quotes GPC').'</td>
                                <td>'.(ini_get('magic_quotes_gpc') ? T_('On') : T_('Off')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('PHP open_basedir').'</td>
                                <td>'.(ini_get('open_basedir') ? T_('On') : T_('Off')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('PHP XML Support').'</td>
                                <td>'.(is_callable('xml_parser_create') ? T_('Yes') : T_('No')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('PHP Exif Support').'</td>
                                <td>'.(is_callable('exif_read_data') ? T_('Yes') : T_('No')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('Disabled PHP Functions').'</td>
                                <td>'.(ini_get('disable_functions') ? ini_get('disable_functions') : T_('None')).'</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="fcms">
                    <table style="width:500px;" class="zebra-striped">
                        <thead>
                            <tr>
                                <th colspan="2">'.T_('Family Connections Information').'</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>'.T_('Registration').'</td>
                                <td>'.($config['registration'] ? T_('On') : T_('Off')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('Account Activation').'</td>
                                <td>'.($config['auto_activate'] ? T_('Administrator') : T_('Auto')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('Site Status').'</td>
                                <td>'.($config['site_off'] ? T_('Off') : T_('On')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('Debug Mode').'</td>
                                <td>'.($config['debug'] ? T_('On') : T_('Off')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('Plugins Turned Off').'</td>
                                <td>'.implode('<br/>', $disabledPlugins).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('Photo Gallery: Full Size Photos').'</td>
                                <td>'.($config['full_size_photos'] ? T_('On (3 photos)') : T_('Off (2 photos)')).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('Photo Gallery: Protected Photos').'</td>
                                <td>'.(defined('UPLOADS') ? T_('Yes') : T_('No')).'</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="database">
                    <table style="width:500px;" class="zebra-striped">
                        <thead>
                            <tr>
                                <th colspan="2">'.T_('Database Information').'</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>'.T_('MySQL Database Version').'</td>
                                <td>'.$mysqlVersion.'</td>
                            </tr>
                            <tr>
                                <td>'.T_('MySQL Client Version').'</td>
                                <td>'.$mysqlClient.'</td>
                            </tr>
                            <tr>
                                <td>'.T_('Database Host').'</td>
                                <td>'.$cfg_mysql_host.'</td>
                            </tr>
                            <tr>
                                <td>'.T_('Database Name').'</td>
                                <td>'.$cfg_mysql_db.'</td>
                            </tr>
                            <tr>
                                <td>'.T_('Database User').'</td>
                                <td>'.$cfg_mysql_user.'</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="server">
                    <table style="width:500px;" class="zebra-striped">
                        <thead>
                            <tr>
                                <th colspan="2">'.T_('Server Information').'</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>'.T_('IP Address').'</td>
                                <td>'.(array_key_exists('SERVER_ADDR', $_SERVER) ? cleanOutput($_SERVER['SERVER_ADDR']) : cleanOutput($_SERVER['LOCAL_ADDR'])).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('Server Type').'</td>
                                <td>'.cleanOutput($_SERVER['SERVER_SOFTWARE']).'</td>
                            </tr>
                            <tr>
                                <td>'.T_('Operating System').'</td>
                                <td>'.PHP_OS.'</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>';

        $this->displayFooter();
    }
}
