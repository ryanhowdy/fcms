<?php
/**
 * Register
 *  
 * PHP versions 4 and 5
 *  
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2007 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
require 'fcms.php';

// Language
$lang = 'en_US';

// Overwrite language
if (isset($_GET['lang']))
{
    $lang = $_GET['lang'];
}

// Setup php-gettext
T_setlocale(LC_MESSAGES, $lang);
T_bindtextdomain('messages', './language');
T_bind_textdomain_codeset('messages', 'UTF-8');
T_textdomain('messages');

if (isset($_GET['feed']))
{
    if ($_GET['feed'] == 'all')
    {
        header("Content-Type: application/rss+xml");
        displayFeedAll();
    }
    elseif ($_GET['feed'] == 'gallery')
    {
        header("Content-Type: application/rss+xml");
        displayFeedPhotoGallery();
    }
    else
    {
        echo "<p>".T_('The RSS feed you requested doesn\'t exist')."</p>";
    }
}
else
{
    header("Content-Type: application/rss+xml");
    displayFeedAll();
}

/**
 * displayFeedAll 
 *
 * Displays RSS 2.0 feed for all updates to the site
 * 
 *  ADDRESSADD      Add address of non-member
 *  ADDRESSEDIT     Edit own address
 *  AVATAR          Change avatar
 *  BOARD           Message board post
 *  CALENDAR        Add date to calendar
 *  DOCS            Added document
 *  GALCATCOM       Commented on category of photos
 *  GALCOM          Commented on photo
 *  GALLERY         Added photo
 *  JOINED          Joined the site (became active)
 *  NEWS            Added family news
 *  NEWSCOM         Commented on family news
 *  POLL            Added poll
 *  POLLCOM         Commented on poll
 *  PRAYERS         Added prayer concern
 *  RECIPES         Added recipe
 *  RECIPECOM       Commented on recipe
 *  STATUS          Added status update
 *  VIDEO           Added video
 *  VIDEOCOM        Commented on video
 *  WHEREISEVERYONE Checked in on foursquare
 * 
 * @author: choc
 * @author: Ryan Haudenschilt <r.haudenschilt@gmail.com>
 * 
 * @return  void
 */
function displayFeedAll ()
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $url     = "http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']; 
    $urlRoot = $url; 

    $pos = strrpos($url, "/"); 
    if ($pos===false)
    { 
        $pos = strrpos($url, "\\"); 
    } 
    if (!($pos===false))
    { 
        $urlRoot = substr($url, 0, $pos); 
    } 

    // Get data
    $whatsNewData = getWhatsNewData(30);
    if ($whatsNewData === false)
    {
        return;
    }

    $output = "<?xml version=\"1.0\"?" . "> 
<rss version=\"2.0\"> 
<channel> 
<title>" . getSiteName() . " - " . T_('What\'s New') . "</title> 
<link>" . $url . "</link> 
<description>" . getSiteName() . " - " . T_('What\'s New') . " " . T_('RSS Feed') . "</description> 
<language>" . T_('lang') . "</language> 
<managingEditor>" . getContactEmail() . "</managingEditor> 
"; 
    
    foreach ($whatsNewData as $line)
    { 
        $title = ""; 
        $link  = ""; 
        $guid  = ""; 

        // Clean the data
        $cId     = (int)$line['id'];
        $cId2    = (int)$line['id2'];
        $cUserid = (int)$line['userid'];
        $cTitle  = html_convert_entities($line['title']);
        
        // Add Address
        if ($line['type'] == 'ADDRESSADD')
        {
            $displayname = getUserDisplayName($cId2);
            $for         = getUserDisplayName($cUserid, 2, false);
            $link        = 'addressbook.php?address='.$cId;
            $title       = sprintf(T_('%s has added address information for %s.'), $displayname, $for);
        }
        // Edit Address
        elseif ($line['type'] == 'ADDRESSEDIT')
        {
            $displayname = getUserDisplayName($cId2);
            $link        = 'addressbook.php?address='.$cId;
            $title       = sprintf(T_('%s has updated his/her address.'), $displayname);
        }
        // Avatar
        elseif ($line['type'] == 'AVATAR')
        {
            $displayname = getUserDisplayName($cUserid);
            $link        = 'profile.php?member='.$cUserid;
            $title       = sprintf(T_('%s has changed his/her picture.'), $displayname);
        }
        // Message Board
        elseif ($line['type'] == 'BOARD')
        {
            $sql = "SELECT min(`id`) AS id 
                    FROM `fcms_board_posts` 
                    WHERE `thread` = ?";

            $minpost = $fcmsDatabase->getRow($sql, $cId2);
            if ($minpost === false)
            {
                // error will be logged, but not displayed
                continue;
            }

            $userName = getUserDisplayName($cUserid);
            $subject  = $cTitle;
            $link     = "messageboard.php?thread=".$cId2;

            $pos = strpos($subject, '#ANOUNCE#');
            if ($pos !== false)
            {
                $subject = substr($subject, 9, strlen($subject)-9);
            }

            if ($cId == $minpost['id'])
            {
                $title = sprintf(T_('%s started the new thread %s.'), $userName, $subject);
            }
            else
            {
                $title = sprintf(T_('%s replied to %s.'), $userName, $subject);
            }
        }
        // Calendar
        elseif ($line['type'] == 'CALENDAR')
        {
            // TODO
            // copy from calendar_class
            $displayname = getUserDisplayName($cUserid);
            $date_date   = gmdate(T_('m-d-y'), strtotime($cId2));
            $date_date2  = gmdate(T_('F j, Y'), strtotime($cId2));
            $link        = 'calendar.php?year='.gmdate('Y', strtotime($date_date2)).'&amp;month='.gmdate('m', strtotime($date_date2)).'&amp;day='.gmdate('d', strtotime($date_date2));
            $title       = sprintf(T_('%s has added a new Calendar entry on %s for %s.'), $displayname, $date_date, $cTitle);
        }
        // Documents
        elseif ($line['type'] == 'DOCS')
        {
            $displayname = getUserDisplayName($cUserid);
            $link        = 'documents.php';
            $title       = sprintf(T_('%s has added a new document (%s).'), $displayname, $cTitle);
        }
        // Comment - Photo Gallery Category
        elseif ($line['type'] == 'GALCATCOM')
        {
            $displayname = getUserDisplayName($cUserid);
            $link        = 'gallery/index.php?uid'.$cId2.'&amp;cid='.(int)$line['id3'];
            $title       = sprintf(T_('%s commented on (%s).'), $displayname, $cTitle);
        }
        // Comment - Photo Gallery
        elseif ($line['type'] == 'GALCOM')
        {
            $displayname = getUserDisplayName($cUserid);
            $link        = 'gallery/index.php?uid=0&amp;cid=comments&amp;pid='.$cId;
            $title       = sprintf(T_('%s commented on the following photo:'), $displayname);
        }
        // Photo Gallery
        elseif ($line['type'] == 'GALLERY')
        {
            $displayname = getUserDisplayName($cUserid);
            $link        = 'gallery/index.php?uid='.$cUserid.'&amp;cid='.$cId;
            $title       = sprintf(T_('%s has added %d new photos to the %s category.'), $displayname, $cId2, $cTitle);
        }
        // New Members
        elseif ($line['type'] == 'JOINED')
        { 
            $displayname = getUserDisplayName($cUserid);
            $link        = "profile.php?member=".$cUserid; 
            $title       = sprintf(T_('%s has joined the website.'), $displayname);
        }
        // Family News
        elseif ($line['type'] == 'NEWS')
        {
            $displayname = getUserDisplayName($cUserid);
            $link        = 'familynews.php?getnews='.$cUserid.'&amp;newsid='.$cId; 
            $title       = sprintf(T_('%s has added %s to his/her Family News.'), $displayname, $cTitle);
        }
        // Comment - Family News
        elseif ($line['type'] == 'NEWSCOM')
        {
            $displayname = getUserDisplayName($cUserid);
            $link        = 'familynews.php?getnews='.$cUserid.'&amp;newsid='.$cId;
            $title       = sprintf(T_('%s commented on Family News %s.'), $displayname, $cTitle);
        }
        // Poll
        elseif ($line['type'] == 'POLL')
        {
            $link  = 'polls.php?id='.$cId;
            $title = sprintf(T_('A new Poll (%s) has been added.'), $cTitle);
        }
        // Comment - Poll
        elseif ($line['type'] == 'POLLCOM')
        {
            $displayname = getUserDisplayName($cUserid);
            $link        = 'polls.php?id='.$cId;
            $title       = sprintf(T_('%s commented on Poll %s.'), $displayname, $cTitle);
        }
        // Prayer Concerns
        elseif ($line['type'] == 'PRAYERS')
        {
            $displayname = getUserDisplayName($cUserid);
            $link        = 'prayers.php';
            $title       = sprintf(T_('%s has added a Prayer Concern for %s.'), $displayname, $cTitle);
        }
        // Recipes
        elseif ($line['type'] == 'RECIPES')
        { 
            $displayname = getUserDisplayName($cUserid);
            $link        = 'recipes.php?category='.$cId2.'&amp;id='.$cId;
            $title       = sprintf(T_('%s has added the recipe %s.'), $displayname, $cTitle);
        }
        // Comment - Recipe
        elseif ($line['type'] == 'RECIPECOM')
        {
            $displayname = getUserDisplayName($cUserid);
            $link        = 'recipes.php?category='.$cId2.'&amp;id='.$cId;
            $title       = sprintf(T_('%s commented on Recipe %s.'), $displayname, $cTitle);
        }
        // Status Update
        elseif ($line['type'] == 'STATUS')
        { 
            $displayname = getUserDisplayName($cUserid);
            $link        = 'home.php';
            $title       = $displayname.': '.$cTitle;
        }
        // Video
        elseif ($line['type'] == 'VIDEO')
        { 
            $displayname = getUserDisplayName($cUserid);
            $link        = 'video.php?u='.$cUserid.'&amp;id='.$cId;
            $title       = sprintf(T_('%s has added a the video %s.'), $displayname, $cTitle);
        }
        // Comment - Video
        elseif ($line['type'] == 'VIDEOCOM')
        {
            $displayname = getUserDisplayName($cUserid);
            $link        = 'video.php?u='.$cUserid.'&amp;id='.$cId;
            $title       = sprintf(T_('%s commented on the video %s.'), $displayname, $cTitle);
        }
        // Where Is Everyone
        elseif ($line['type'] == 'WHEREISEVERYONE')
        { 
            $displayname = getUserDisplayName($cUserid);
            $link        = 'whereiseveryone.php';
            $title       = sprintf(T_('%s visited %s.'), $displayname, $cTitle);
        }
        
        $output .= "
<item>
<title><![CDATA[$title]]></title> 
<pubDate>".gmdate(T_('D, d M Y H:i:s'), strtotime($line['date']))." GMT</pubDate> 
<link>$urlRoot/$link</link> 
<guid isPermaLink=\"false\"><![CDATA[$urlRoot $title ".gmdate(T_('D, d M Y H:i:s'), strtotime($line['date']))."]]></guid> 
</item>"; 
    } 
    
    $output .= "
</channel>
</rss>"; 

    echo $output;
}

/**
 * displayFeedPhotoGallery 
 * 
 * @return void
 */
function displayFeedPhotoGallery () 
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    $url     = "http://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']; 
    $urlroot = $url; 

    $pos = strrpos($url, "/"); 
    if ($pos===false)
    { 
        $pos = strrpos($url, "\\"); 
    } 

    if (!($pos===false))
    {
        $urlroot = substr($url, 0, $pos); 
    } 

    $lastday = time() - (84 * 60 * 60 * 24); // 12 weeks 

    $sql = "SELECT `caption`, p.`user`, `filename`, p.`date`, `name` 
            FROM `fcms_gallery_photos` AS p, `fcms_category` As c
            WHERE p.`category` = c.`id` 
            AND UNIX_TIMESTAMP(p.`date`) >= ?
            ORDER BY p.`date`"; 

    $rows = $fcmsDatabase->getRows($sql, $lastday);
    if ($rows === false)
    {
        print "Error getting data.";

        return;
    }

    $output = "<?xml version=\"1.0\"?" . "> 
<rss version=\"2.0\"> 
<channel> 
<title>" . getSiteName() . " - " . T_('Photo Gallery') . "</title> 
<link>" . $url . "</link> 
<description>" . getSiteName() . " - " . T_('Photo Gallery') . " " . T_('RSS Feed') . "</description> 
<language>" . T_('lang') . "</language> 
<managingEditor>" . getContactEmail() . "</managingEditor> 
";

    if (count($rows) > 0)
    {
        foreach ($rows as $line)
        {
            $title = htmlentities($line['caption']);
            if ($title == "")
            {
                $title = htmlentities($line['name']);
            }
            $output .= "<item><title><![CDATA[$title]]></title> 
<pubDate>" . gmdate('D, d M Y H:i:s', strtotime($line['date'])) . " GMT</pubDate> 
<link>".htmlentities( $urlroot."/gallery/photos/member".$line['user']."/".$line['filename'])."</link>              
<description><![CDATA[<img src=\"$urlroot/gallery/photos/member".$line['user']."/".$line['filename']."\" border=\"0\" />]]></description> 
<enclosure url=\"".$urlroot."/gallery/photos/member".$line['user']."/".$line['filename']."\" type=\"". returnMIMEType("./gallery/photos/member".$line['user']."/".$line['filename']) ."\" length=\"". filesize("./gallery/photos/member".$line['user']."/".$line['filename'])."\" /> 
<guid isPermaLink=\"true\"><![CDATA[".$urlroot."/gallery/photos/member".$line['user']."/".$line['filename']."]]></guid> 
</item> 
"; 
        } 
    }
    $output .= "</channel></rss>"; 
    echo $output; 
}

/**
 * returnMIMEType 
 * 
 * @param string $filename A file to check mime type
 * 
 * @return void
 */
function returnMIMEType ($filename) 
{ 
    preg_match("|\.([a-z0-9]{2,4})$|i", $filename, $fileSuffix); 

    switch(strtolower($fileSuffix[1]))
    { 
        case "jpg":
        case "jpeg":
        case "jpe":
            return "image/jpg";
            break;
        case "png": 
        case "gif": 
        case "bmp": 
        case "tiff": 
            return "image/".strtolower($fileSuffix[1]); 
            break;
        case "html": 
        case "htm": 
        case "php": 
            return "text/html"; 
            break;
        case "txt" : 
            return "text/plain"; 
            break;
        case "mpeg": 
        case "mpg": 
        case "mpe": 
            return "video/mpeg"; 
            break;
        case "mp3": 
            return "audio/mpeg3"; 
            break;
        case "wav": 
            return "audio/wav"; 
            break;
        case "aiff": 
        case "aif": 
            return "audio/aiff"; 
            break;
        case "avi": 
            return "video/msvideo"; 
            break;
        case "wmv": 
            return "video/x-ms-wmv"; 
            break;
        case "mov" : 
            return "video/quicktime"; 
            break;
        default : 
            if (function_exists("mime_content_type"))
            {
                $fileSuffix = mime_content_type($filename); 
            } 
            return "unknown/".trim($fileSuffix[0], "."); 
            break;
    } 
 }

/**
 * html_convert_entities
 *
 * convert named HTML entities to XML-compatible numeric entities
 *
 * http://inanimatt.com/php-convert-entities.html
 * 
 * @param string $string 
 * 
 * @return string
 */
function html_convert_entities($string)
{
    return preg_replace_callback('/&([a-zA-Z][a-zA-Z0-9]+);/', 'convert_entity', $string);
}

/**
 * convert_entity 
 * 
 * Swap HTML named entity with its numeric equivalent. If the entity
 * isn't in the lookup table, this function returns a blank, which
 * destroys the character in the output - this is probably the 
 * desired behaviour when producing XML.
 *
 * http://inanimatt.com/php-convert-entities.html
 * 
 * @param string $matches 
 * 
 * @return string
 */
function convert_entity($matches)
{
    static $table = array(
        'quot'     => '&#34;',
        'amp'      => '&#38;',
        'lt'       => '&#60;',
        'gt'       => '&#62;',
        'OElig'    => '&#338;',
        'oelig'    => '&#339;',
        'Scaron'   => '&#352;',
        'scaron'   => '&#353;',
        'Yuml'     => '&#376;',
        'circ'     => '&#710;',
        'tilde'    => '&#732;',
        'ensp'     => '&#8194;',
        'emsp'     => '&#8195;',
        'thinsp'   => '&#8201;',
        'zwnj'     => '&#8204;',
        'zwj'      => '&#8205;',
        'lrm'      => '&#8206;',
        'rlm'      => '&#8207;',
        'ndash'    => '&#8211;',
        'mdash'    => '&#8212;',
        'lsquo'    => '&#8216;',
        'rsquo'    => '&#8217;',
        'sbquo'    => '&#8218;',
        'ldquo'    => '&#8220;',
        'rdquo'    => '&#8221;',
        'bdquo'    => '&#8222;',
        'dagger'   => '&#8224;',
        'Dagger'   => '&#8225;',
        'permil'   => '&#8240;',
        'lsaquo'   => '&#8249;',
        'rsaquo'   => '&#8250;',
        'euro'     => '&#8364;',
        'fnof'     => '&#402;',
        'Alpha'    => '&#913;',
        'Beta'     => '&#914;',
        'Gamma'    => '&#915;',
        'Delta'    => '&#916;',
        'Epsilon'  => '&#917;',
        'Zeta'     => '&#918;',
        'Eta'      => '&#919;',
        'Theta'    => '&#920;',
        'Iota'     => '&#921;',
        'Kappa'    => '&#922;',
        'Lambda'   => '&#923;',
        'Mu'       => '&#924;',
        'Nu'       => '&#925;',
        'Xi'       => '&#926;',
        'Omicron'  => '&#927;',
        'Pi'       => '&#928;',
        'Rho'      => '&#929;',
        'Sigma'    => '&#931;',
        'Tau'      => '&#932;',
        'Upsilon'  => '&#933;',
        'Phi'      => '&#934;',
        'Chi'      => '&#935;',
        'Psi'      => '&#936;',
        'Omega'    => '&#937;',
        'alpha'    => '&#945;',
        'beta'     => '&#946;',
        'gamma'    => '&#947;',
        'delta'    => '&#948;',
        'epsilon'  => '&#949;',
        'zeta'     => '&#950;',
        'eta'      => '&#951;',
        'theta'    => '&#952;',
        'iota'     => '&#953;',
        'kappa'    => '&#954;',
        'lambda'   => '&#955;',
        'mu'       => '&#956;',
        'nu'       => '&#957;',
        'xi'       => '&#958;',
        'omicron'  => '&#959;',
        'pi'       => '&#960;',
        'rho'      => '&#961;',
        'sigmaf'   => '&#962;',
        'sigma'    => '&#963;',
        'tau'      => '&#964;',
        'upsilon'  => '&#965;',
        'phi'      => '&#966;',
        'chi'      => '&#967;',
        'psi'      => '&#968;',
        'omega'    => '&#969;',
        'thetasym' => '&#977;',
        'upsih'    => '&#978;',
        'piv'      => '&#982;',
        'bull'     => '&#8226;',
        'hellip'   => '&#8230;',
        'prime'    => '&#8242;',
        'Prime'    => '&#8243;',
        'oline'    => '&#8254;',
        'frasl'    => '&#8260;',
        'weierp'   => '&#8472;',
        'image'    => '&#8465;',
        'real'     => '&#8476;',
        'trade'    => '&#8482;',
        'alefsym'  => '&#8501;',
        'larr'     => '&#8592;',
        'uarr'     => '&#8593;',
        'rarr'     => '&#8594;',
        'darr'     => '&#8595;',
        'harr'     => '&#8596;',
        'crarr'    => '&#8629;',
        'lArr'     => '&#8656;',
        'uArr'     => '&#8657;',
        'rArr'     => '&#8658;',
        'dArr'     => '&#8659;',
        'hArr'     => '&#8660;',
        'forall'   => '&#8704;',
        'part'     => '&#8706;',
        'exist'    => '&#8707;',
        'empty'    => '&#8709;',
        'nabla'    => '&#8711;',
        'isin'     => '&#8712;',
        'notin'    => '&#8713;',
        'ni'       => '&#8715;',
        'prod'     => '&#8719;',
        'sum'      => '&#8721;',
        'minus'    => '&#8722;',
        'lowast'   => '&#8727;',
        'radic'    => '&#8730;',
        'prop'     => '&#8733;',
        'infin'    => '&#8734;',
        'ang'      => '&#8736;',
        'and'      => '&#8743;',
        'or'       => '&#8744;',
        'cap'      => '&#8745;',
        'cup'      => '&#8746;',
        'int'      => '&#8747;',
        'there4'   => '&#8756;',
        'sim'      => '&#8764;',
        'cong'     => '&#8773;',
        'asymp'    => '&#8776;',
        'ne'       => '&#8800;',
        'equiv'    => '&#8801;',
        'le'       => '&#8804;',
        'ge'       => '&#8805;',
        'sub'      => '&#8834;',
        'sup'      => '&#8835;',
        'nsub'     => '&#8836;',
        'sube'     => '&#8838;',
        'supe'     => '&#8839;',
        'oplus'    => '&#8853;',
        'otimes'   => '&#8855;',
        'perp'     => '&#8869;',
        'sdot'     => '&#8901;',
        'lceil'    => '&#8968;',
        'rceil'    => '&#8969;',
        'lfloor'   => '&#8970;',
        'rfloor'   => '&#8971;',
        'lang'     => '&#9001;',
        'rang'     => '&#9002;',
        'loz'      => '&#9674;',
        'spades'   => '&#9824;',
        'clubs'    => '&#9827;',
        'hearts'   => '&#9829;',
        'diams'    => '&#9830;',
        'nbsp'     => '&#160;',
        'iexcl'    => '&#161;',
        'cent'     => '&#162;',
        'pound'    => '&#163;',
        'curren'   => '&#164;',
        'yen'      => '&#165;',
        'brvbar'   => '&#166;',
        'sect'     => '&#167;',
        'uml'      => '&#168;',
        'copy'     => '&#169;',
        'ordf'     => '&#170;',
        'laquo'    => '&#171;',
        'not'      => '&#172;',
        'shy'      => '&#173;',
        'reg'      => '&#174;',
        'macr'     => '&#175;',
        'deg'      => '&#176;',
        'plusmn'   => '&#177;',
        'sup2'     => '&#178;',
        'sup3'     => '&#179;',
        'acute'    => '&#180;',
        'micro'    => '&#181;',
        'para'     => '&#182;',
        'middot'   => '&#183;',
        'cedil'    => '&#184;',
        'sup1'     => '&#185;',
        'ordm'     => '&#186;',
        'raquo'    => '&#187;',
        'frac14'   => '&#188;',
        'frac12'   => '&#189;',
        'frac34'   => '&#190;',
        'iquest'   => '&#191;',
        'Agrave'   => '&#192;',
        'Aacute'   => '&#193;',
        'Acirc'    => '&#194;',
        'Atilde'   => '&#195;',
        'Auml'     => '&#196;',
        'Aring'    => '&#197;',
        'AElig'    => '&#198;',
        'Ccedil'   => '&#199;',
        'Egrave'   => '&#200;',
        'Eacute'   => '&#201;',
        'Ecirc'    => '&#202;',
        'Euml'     => '&#203;',
        'Igrave'   => '&#204;',
        'Iacute'   => '&#205;',
        'Icirc'    => '&#206;',
        'Iuml'     => '&#207;',
        'ETH'      => '&#208;',
        'Ntilde'   => '&#209;',
        'Ograve'   => '&#210;',
        'Oacute'   => '&#211;',
        'Ocirc'    => '&#212;',
        'Otilde'   => '&#213;',
        'Ouml'     => '&#214;',
        'times'    => '&#215;',
        'Oslash'   => '&#216;',
        'Ugrave'   => '&#217;',
        'Uacute'   => '&#218;',
        'Ucirc'    => '&#219;',
        'Uuml'     => '&#220;',
        'Yacute'   => '&#221;',
        'THORN'    => '&#222;',
        'szlig'    => '&#223;',
        'agrave'   => '&#224;',
        'aacute'   => '&#225;',
        'acirc'    => '&#226;',
        'atilde'   => '&#227;',
        'auml'     => '&#228;',
        'aring'    => '&#229;',
        'aelig'    => '&#230;',
        'ccedil'   => '&#231;',
        'egrave'   => '&#232;',
        'eacute'   => '&#233;',
        'ecirc'    => '&#234;',
        'euml'     => '&#235;',
        'igrave'   => '&#236;',
        'iacute'   => '&#237;',
        'icirc'    => '&#238;',
        'iuml'     => '&#239;',
        'eth'      => '&#240;',
        'ntilde'   => '&#241;',
        'ograve'   => '&#242;',
        'oacute'   => '&#243;',
        'ocirc'    => '&#244;',
        'otilde'   => '&#245;',
        'ouml'     => '&#246;',
        'divide'   => '&#247;',
        'oslash'   => '&#248;',
        'ugrave'   => '&#249;',
        'uacute'   => '&#250;',
        'ucirc'    => '&#251;',
        'uuml'     => '&#252;',
        'yacute'   => '&#253;',
        'thorn'    => '&#254;',
        'yuml'     => '&#255;'
    );

    // Entity not found? Destroy it.
    return isset($table[$matches[1]]) ? $table[$matches[1]] : '';
}
