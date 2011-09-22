<?php
/**
 * Video
 * 
 * PHP version 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     2.5
 */
session_start();

define('URL_PREFIX', '');

require 'fcms.php';

load('socialmedia', 'vimeo');

// Check that the user is logged in
isLoggedIn();

// Globals
$currentUserId = cleanInput($_SESSION['login_id'], 'int');

$TMPL = array(
    'sitename'      => getSiteName(),
    'nav-link'      => getNavLinks(),
    'pagetitle'     => T_('Video'),
    'path'          => URL_PREFIX,
    'displayname'   => getUserDisplayName($currentUserId),
    'version'       => getCurrentVersion(),
    'year'          => date('Y')
);

control();
exit();


/**
 * control 
 * 
 * The controlling structure for this script.
 * 
 * @return void
 */
function control ()
{
    global $book;

    if (isset($_GET['upload']))
    {
        displayUpload();
    }
    else
    {
        displayLatest();
    }
}

/**
 * displayHeader 
 * 
 * @return void
 */
function displayHeader ()
{
    global $currentUserId, $TMPL;

    $TMPL['javascript'] = '
<script type="text/javascript">
//<![CDATA[
Event.observe(window, \'load\', function() {
    initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\');
});
//]]>
</script>';

    include_once getTheme($currentUserId).'header.php';

    echo '
        <div id="video" class="centercontent clearfix">

            <div id="actions_menu" class="clearfix">
                <ul>
                    <li><a href="?upload">'.T_('Upload Video').'</a></li>
                </ul>
            </div>';
}

/**
 * displayFooter 
 * 
 * @return void
 */
function displayFooter ()
{
    global $currentUserId, $TMPL;

    echo '
        </div><!-- /centercontent -->';

    include_once getTheme($currentUserId).'footer.php';
}

/**
 * displayUpload 
 * 
 * @return void
 */
function displayUpload ()
{
    displayHeader();

    $vimeoData = getVimeoConfigData();

    if (empty($vimeoData['vimeo_key']) || empty($vimeoData['vimeo_secret']))
    {
        echo '
            <div class="info-alert">
                <h2>'.T_('Vimeo isn\'t Configured Yet.').'</h2>
                <p>'.T_('Unfortunately, the Video Gallery relies on Vimeo and your website administrator has not set up Vimeo yet.').'</p>
            </div>';

        displayFooter();
        return;
    }

    $vimeo = new phpVimeo($vimeoData['vimeo_key'], $vimeoData['vimeo_secret'], $_SESSION['oauth_access_token'], $_SESSION['oauth_access_token_secret']);

    try {
        $video_id = $vimeo->upload('uploads/intro.wmv');

        if ($video_id) {
            echo '<a href="http://vimeo.com/' . $video_id . '">Upload successful!</a>';

            $vimeo->call('vimeo.videos.setPrivacy', array('privacy' => 'nobody', 'video_id' => $video_id));
            $vimeo->call('vimeo.videos.setTitle', array('title' => 'YOUR TITLE', 'video_id' => $video_id));
            $vimeo->call('vimeo.videos.setDescription', array('description' => 'YOUR_DESCRIPTION', 'video_id' => $video_id));
        }
        else {
            echo "Video file did not exist!";
        }
    }
    catch (VimeoAPIException $e) {
        echo "Encountered an API error -- code {$e->getCode()} - {$e->getMessage()}";
    }
    displayFooter();
}

/**
 * displayLatest 
 * 
 * @return void
 */
function displayLatest ()
{
    global $currentUserId;

    displayHeader();

    $vimeoData = getVimeoConfigData();
    $userData  = getVimeoUserData($currentUserId);

    if (empty($vimeoData['vimeo_key']) || empty($vimeoData['vimeo_secret']))
    {
        echo '
            <div class="info-alert">
                <h2>'.T_('Vimeo isn\'t Configured Yet.').'</h2>
                <p>'.T_('Unfortunately, the Video Gallery relies on Vimeo and your website administrator has not set up Vimeo yet.').'</p>
            </div>';

        displayFooter();
        return;
    }

    if (empty($userData['vimeo_access_token']) || empty($userData['vimeo_access_token_secret']))
    {
        echo '
            <div class="info-alert">
                <h2>'.T_('Your Account isn\'t connected to Vimeo yet.').'</h2>
                <p>'.T_('Unfortunately, you must have a Vimeo accont to use the Video Gallery.').'</p>
                <p><a href="settings.php?view=socialmedia">'.T_('Connect your Vimeo account.').'</a></p>
            </div>';

        displayFooter();
        return;
    }


    // Create the object and enable caching
    $vimeo = new phpVimeo($vimeoData['vimeo_key'], $vimeoData['vimeo_secret']);
    $vimeo->enableCache(phpVimeo::CACHE_FILE, './cache', 300);

    $vimeo->setToken($userData['vimeo_access_token'], $userData['vimeo_access_token_secret']);

    // Do an authenticated call
    try
    {
        $response = $vimeo->call('vimeo.videos.getUploaded');
    }
    catch (VimeoAPIException $e)
    {
        echo "Encountered an API error -- code {$e->getCode()} - {$e->getMessage()}";
    }

    if (isset($response))
    {
printr($response);
        echo 'Videos:<br/>';

        $videos = $response->videos->video;

$oembed_endpoint = 'http://vimeo.com/api/oembed';

        foreach ($videos as $video)
        {
            // Grab the video url from the url, or use default
            $video_url = 'http://vimeo.com/'.$video->id;

            // Create the URLs
            $json_url = $oembed_endpoint . '.json?url=' . rawurlencode($video_url) . '&width=640';
            $xml_url  = $oembed_endpoint . '.xml?url=' . rawurlencode($video_url) . '&width=640';

            // Load in the oEmbed XML
            $oembed = simplexml_load_string(curl_get($xml_url));

            echo html_entity_decode($oembed->html);
        }
    }



    displayFooter();
}

// Curl helper function
function curl_get($url) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    $return = curl_exec($curl);
    curl_close($curl);
    return $return;
}
