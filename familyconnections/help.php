<?php
/**
 * Help
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
session_start();

define('URL_PREFIX', '');
define('GALLERY_PREFIX', 'gallery/');

require 'fcms.php';

init();

$page  = new Page($fcmsError, $fcmsDatabase, $fcmsUser);

exit();

class Page
{
    private $fcmsError;
    private $fcmsDatabase;
    private $fcmsUser;

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct ($fcmsError, $fcmsDatabase, $fcmsUser)
    {
        $this->fcmsError         = $fcmsError;
        $this->fcmsDatabase      = $fcmsDatabase;
        $this->fcmsUser          = $fcmsUser;

        $this->fcmsTemplate = array(
            'currentUserId' => $this->fcmsUser->id,
            'sitename'      => getSiteName(),
            'nav-link'      => getNavLinks(),
            'pagetitle'     => T_('Help'),
            'path'          => URL_PREFIX,
            'displayname'   => getUserDisplayName($this->fcmsUser->id),
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
        if (isset($_GET['topic']))
        {
            $topic = $_GET['topic'];

            if ($topic == 'photo')
            {
                $this->displayPhotoGallery();
            }
            elseif ($topic == 'video')
            {
                $this->displayVideoGallery();
            }
            elseif ($topic == 'settings')
            {
                $this->displaySettings();
            }
            elseif ($topic == 'address')
            {
                $this->displayAddressBook();
            }
            elseif ($topic == 'admin')
            {
                $this->displayAdministration();
            }
            else
            {
                $this->displayHome();
            }
        }
        else
        {
            $this->displayHome();
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
<script type="text/javascript">
Event.observe(window, \'load\', function() { initChatBar(\''.T_('Chat').'\', \''.$TMPL['path'].'\'); });
</script>';

        require_once getTheme($this->fcmsUser->id).'header.php';

        echo '
        <div id="help" class="centercontent">

            <div id="leftcolumn">
                <h3>'.T_('Topics').'</h3>
                <ul class="menu">
                    <li><a href="?topic=photo">'.T_('Photo Gallery').'</a></li>
                    <li><a href="?topic=video">'.T_('Video Gallery').'</a></li>
                    <li><a href="?topic=settings">'.T_('Personal Settings').'</a></li>
                    <li><a href="?topic=address">'.T_('Address Book').'</a></li>
                    <li><a href="?topic=admin">'.T_('Administration').'</a></li>
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
            </div><!--/maincolumn-->

        </div><!--/centercontent-->';

        require_once getTheme($this->fcmsUser->id).'footer.php';
    }

    /**
     * displayHome 
     * 
     * @return void
     */
    function displayHome ()
    {
        $this->displayHeader();
        echo '
                <h2>'.T_('Welcome to the Help section.').'</h2>
                <p>'.T_('Browse the topics to the left to find help on the most frequently asked topics.').'</p>
                <p>&nbsp;</p>
                <h3>'.T_('Need more help?').'</h3>
                <p>'.T_('Check out the support forum for more help.').'</p>
                <p><a href="http://familycms.tenderapp.com/discussions">'.T_('Support Forum').'</a></p>';
        $this->displayFooter();
    }

    /**
     * displayPhotoGallery 
     * 
     * @return void
     */
    function displayPhotoGallery ()
    {
        $this->displayHeader();
        echo '
            <h4>'.T_('Photo Gallery').'</h4>
            <p><a href="#gallery-howworks">'.T_('How does the Photo Gallery work?').'</a></p>
            <p><a href="#gallery-addphoto">'.T_('How do I add a photo?').'</a></p>
            <p><a href="#gallery-chgphoto">'.T_('How do I edit/change a photo?').'</a></p>
            <p><a href="#gallery-delphoto">'.T_('How do I delete a photo?').'</a></p>
            <p><a href="#gallery-addcat">'.T_('How do I add a category?').'</a></p>
            <p><a href="#gallery-chgcat">'.T_('How do I rename a category?').'</a></p>
            <p><a href="#gallery-delcat">'.T_('How do I delete a category?').'</a></p>

            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="gallery-howworks">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'.T_('How does the Photo Gallery work?').'</b></p>
            <p>'.T_('Each member of the website has his/her own Category on the Photo Gallery.  This category will not show up until that member creates a new sub-category and uploads at least one photo.  You can not upload photos until you have created a category.  It is best to create a new category each time you upload a new group of photos.  This helps create a more organized Photo Gallery.').'</p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="gallery-addphoto">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'.T_('How do I add a photo?').'</b></p>
            <ol>
            <li>'.T_('Choose <a href="gallery/index.php?action=upload">Upload Photos</a> from the <a href="gallery/index.php">Photo Gallery</a> menu.').'</li>
            <li>'.T_('Choose a category from the drop down menu.').'<br/>
                <small>'.T_('Note: You must have at least one category to upload photos. If you do not have a existing category you must add a category first.').'</small>
            </li>
            <li>'.T_('Click the browse button to browse your computer for the desired photo to upload.').'<br/>'.T_('If the photo you are uploading needs rotated click \'Upload Options\' and two radio buttons will drop in above the photo caption.').'<br/>
                <small>'.T_('Note: You must have JavaScript enabled to use the rotation feature.').'</small>
            </li>
            <li>'.T_('Fill in the caption (description of the photo).').'</li>
            <li>'.T_('Click the <b>Add Photos</b> button.').'</li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="gallery-chgphoto">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'.T_('How do I edit/change a photo?').'</b></p>
            <p>'.T_('You can only edit/change the photo\'s caption and category.').'</p>
            <ol>
            <li>'.T_('Navigate to the photo you would like to edit.').'</li>
            <li>'.T_('Click the edit button <img src="themes/default/images/edit.gif"/> located above the photo and to the right.').'</li>
            <li>'.T_('To change the category: choose the new category from the dropdown menu above the photo.').'<br/>'.T_('To edit/change the caption: make your changes in the text field area below the photo.').'</li>
            <li>'.T_('Click the submit changes button to finish your changes.').'</li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="gallery-delphoto">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'.T_('How do I delete a photo?').'</b></p>
            <ol>
                <li>'.T_('Navigate to the photo you would like to edit.').'</li>
                <li>'.T_('Click the delete button <img src="themes/default/images/delete.gif"/> located above the photo and to the right.').'</li>
                <li>'.T_('You will be prompted with a message asking if you are sure you want to delete that photo, click Ok.').'</li>
            </ol>
            <p>
                <small>'.T_('Note: you can only delete your own photos.  Once you delete a photo it is gone forever, you cannot undo a delete.').'</small>
            </p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="gallery-addcat">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'.T_('How do I add a category?').'</b></p>
            <ol>
                <li>'.T_('Choose <a href="gallery/index.php?action=category">Create/Edit Category</a> from the <a href="gallery/index.php">Photo Gallery</a> menu.').'</li>
                <li>'.T_('Fill out the category name.').'</li>
                <li>'.T_('Click the <b>Add Category</b> button.').'</li>
            </ol>
            <p>'.T_('A list of previously created categories will be listed below.').'</p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="gallery-chgcat">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'.T_('How do I rename a category?').'</b></p>
            <ol>
                <li>'.T_('Choose <a href="gallery/index.php?action=category">Create/Edit Category</a> from the <a href="gallery/index.php">Photo Gallery</a> menu.').'</li>
                <li>'.T_('Scroll down to the list of categories and find the one you want to change.').'</li>
                <li>'.T_('Make the desired change.').'</li>
                <li>'.T_('Click the edit button <img src="themes/default/images/edit.gif"/> located to the right of the category name.').'</li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="gallery-delcat">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'.T_('How do I delete a category?').'</b></p>
            <ol>
                <li>'.T_('Choose <a href="gallery/index.php?action=category">Create/Edit Category</a> from the <a href="gallery/index.php">Photo Gallery</a> menu.').'</li>
                <li>'.T_('Scroll down to the list of categories and find the one you want to delete.').'</li>
                <li>'.T_('Click the delete button <img src="themes/default/images/delete.gif"/> located to the right of the category name.').'</li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>';
        $this->displayFooter();
    }

    /**
     * displayVideoGallery 
     * 
     * @return void
     */
    function displayVideoGallery ()
    {
        $this->displayHeader();
        echo '
            <h4>'.T_('Video Gallery').'</h4>
            <p><a href="#video-youtube-private">'.T_('YouTube Private Videos.').'</a></p>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="video-youtube-private">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'.T_('YouTube Private Videos').'</b></p>
            <p>'.T_('When you connect your Family Connections account with YouTube, a unique token is created which grants your Family Connections account access to view data on YouTube, just like if you were logged into YouTube.').'</p>
            <p>'.T_('In order to keep your videos private on YouTube, but public to the members of your family site, Family Connections will use your unique token to let other members view your private videos.').'</p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>';
        $this->displayFooter();
    }

    /**
     * displaySettings 
     * 
     * @return void
     */
    function displaySettings ()
    {
        $this->displayHeader();
        echo '
            <h4>'.T_('Personal Settings').'</h4>
            <p><a href="#settings-avatar">'.T_('How do I add/change my avatar?').'</a></p>
            <p><a href="#settings-theme">'.T_('How do I change my theme?').'</a></p>
            <p><a href="#settings-password">'.T_('How do I change my password?').'</a></p>
            <p><a name="settings-avatar">&nbsp;</a></p>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><b>'.T_('How do I add/change my avatar?').'</b></p>
            <p>'.T_('An avatar is just a graphical representation of a person.  You can upload a picture of yourself or any picture that you feel represents you.').'</p>
            <ol>
                <li>'.T_('Click on the <a href="settings.php">My Settings</a> link in the top right hand corner of the site.').'</li>
                <li>'.T_('Click the browse button, which will pop open a menu allowing you to search your computer for an avatar.  (Avatar\'s must be one of the following file types .jpg, .jpeg .gif or .bmp or .png)').'</li>
                <li>'.T_('Once you have choosen your avatar, scroll down to the bottom of the Settings page and click the Submit button.').'</li>
            </ol>
            <p>'.T_('Note: You can upload animated avatar\'s as long as the are smaller than 80 pixels x 80 pixels.  Uploading an avatar larger than this will result in the loss of animation.').'</p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="settings-theme">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'.T_('How do I change my theme?').'</b></p>
            <ol>
                <li>'.T_('Click on the <a href="settings.php">My Settings</a> link in the top right hand corner of the site.').'</li>
                <li>'.T_('Choose your theme from the drop down menu.').'</li>
                <li>'.T_('Scroll down to the bottom of the Settings page and click the Submit button.').'</li>
                <li>'.T_('Click continue and your theme will automatically be applied to the site.').'</li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="settings-password">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'.T_('How do I change my password?').'</b></p>
            <ol>
                <li>'.T_('Click on the <a href="settings.php">My Settings</a> link in the top right hand corner of the site.').'</li>
                <li>'.T_('Type in your new password.').'</li>
                <li>'.T_('Click the Submit button.').'</li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>';
        $this->displayFooter();
    }

    /**
     * displayAddressBook 
     * 
     * @return void
     */
    function displayAddressBook ()
    {
        $this->displayHeader();
        echo '
            <h4>'.T_('Address Book').'</h4>
            <p><a href="#address-massemail">'.T_('How do I email multiple people (Mass Email)?').'</a></p>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="address-massemail">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'.T_('How do I email multiple people (Mass Email)?').'</b></p>
            <ol>
                <li>'.T_('Check the checkboxes to the right of the email addresses of the members you want to email.').'</li>
                <li>'.T_('Click the <b>Email</b> button at the bottom right hand corner of the address book.').'</li>
                <li>'.T_('Fill out the email form (similar to the contact form) and click <b>Send Mass Email</b>.').'</li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>';
        $this->displayFooter();
    }

    /**
     * displayAdministration 
     * 
     * @return void
     */
    function displayAdministration ()
    {
        $this->displayHeader();
        echo '
            <h4>'.T_('Administration').'</h4>

            <p><a href="#adm-access">'.T_('Member Access Levels').'</a></p>
            <p><a href="#adm-sections-add">'.T_('How do I add an optional section?').'</a></p>
            <p><a href="#adm-sections-nav">'.T_('How do I change the site navigation?').'</a></p>
            <p><a href="#adm-protect-photos">'.T_('How do I protect my photos from un-authorized users?').'</a></p>

            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="adm-access">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'.T_('Member Access Levels').'</b></p>
            <p>'.T_('Family Connections has ten different member access levels.  These levels are meant to limit the amount of access each family member has to the website.').'</p>
            <ol>
                <li><b>'.T_('Admin').'</b> - '.T_('this is the access level given to the account that was setup during the installation of FCMS. This is the only level that has the ability to change other members access levels. This level can add, update and delete all information on the site.').'</li>
                <li><b>'.T_('Helper').'</b> - '.T_('this access level has all the same priveleges of the Member level, but can also run the latest awards, can add, update and delete poll questions, and add, update and delete message board posts.').'</li>
                <li><b>'.T_('Member (default)').'</b> - '.T_('this access level can add, update and delete all information they have contributed to the site. They have view only access to other member\'s information.').'</li>
                <li><b>'.T_('Non-Photographer').'</b> - '.T_('this access level has all the same priveleges of the Member level, but cannot add, update or delete photos from the Photo Gallery.').'</li>
                <li><b>'.T_('Non-Poster').'</b> - '.T_('this access level has all the same priveleges of the Member level, but cannot add, update or delete posts from the Message Board.').'</li>
                <li><b>'.T_('Commenter').'</b> - '.T_('this access level can only add comments to Photos, Family News and can reply to posts on the Message Board.  Has view access to all other sections.').'</li>
                <li><b>'.T_('Poster').'</b> - '.T_('this access level can add, update and delete their own Message Board posts only.  Has view access to all other sections.').'</li>
                <li><b>'.T_('Photographer').'</b> - '.T_('this access level can add, update and delete their own Photos only.  Has view access to all other sections.').'</li>
                <li><b>'.T_('Blogger').'</b> - '.T_('this access level can add, update and delete their own Family News entries only.  Has view access to all other sections.').'</li>
                <li><b>'.T_('Guest').'</b> - '.T_('this access level has view only access to the site.').'</li>
            </ol>
            <br/>
            <table class="mem-access" cellpadding="0" cellspacing="0">
                <thead>
                    <tr><th rowspan="2">'.T_('Access Level').'</th><th colspan="6">'.T_('Access Rights').'</th></tr>
                    <tr>
                        <th>'.T_('Admininstration').'</th>
                        <th>'.T_('Photo Gallery').'</th>
                        <th>'.T_('Message Board').'</th>
                        <th>'.T_('Address Book').'</th>
                        <th>'.T_('Family News').'</th>
                        <th>'.T_('Prayer Concerns').'</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="level_name">'.T_('1. Admin').'</td>
                        <td class="y">'.T_('Yes').'*</td>
                        <td class="y">'.T_('Yes').'*</td>
                        <td class="y">'.T_('Yes').'*</td>
                        <td class="y">'.T_('Yes').'*</td>
                        <td class="y">'.T_('Yes').'*</td>
                        <td class="y">'.T_('Yes').'*</td>
                    </tr>
                    <tr>
                        <td class="level_name">'.T_('2. Helper').'</td>
                        <td class="y">'.T_('Yes').'^</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="y">'.T_('Yes').'</td>
                    </tr>
                    <tr>
                        <td class="level_name">'.T_('3. Member').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="y">'.T_('Yes').'</td>
                    </tr>
                    <tr>
                        <td class="level_name">'.T_('4. Non-Photographer').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="y">'.T_('Yes').'</td>
                    </tr>
                    <tr>
                        <td class="level_name">'.T_('5. Non-Poster').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="y">'.T_('Yes').'</td>
                    </tr>
                    <tr>
                        <td class="level_name">'.T_('6. Commenter').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td>'.T_('Comment Only').'</td>
                        <td>'.T_('Comment Only').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td>'.T_('Comment Only').'</td>
                        <td class="n">'.T_('No').'</td>
                    </tr>
                    <tr>
                        <td class="level_name">'.T_('7. Poster').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="n">'.T_('No').'</td>
                    </tr>
                    <tr>
                        <td class="level_name">'.T_('8. Photographer').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="n">'.T_('No').'</td>
                    </tr>
                    <tr>
                        <td class="level_name">'.T_('9. Blogger').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="y">'.T_('Yes').'</td>
                        <td class="n">'.T_('No').'</td>
                    </tr>
                    <tr>
                        <td class="level_name">'.T_('10. Guest').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="n">'.T_('No').'</td>
                        <td class="n">'.T_('No').'</td>
                    </tr>
                </tbody>
            </table>
            <p>* '.T_('Can add/edit/delete all members information').'<br/>^ '.T_('Has limited access to Administration').'</p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>

            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="adm-sections-add">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'.T_('How do I add an optional section?').'</b></p>
            <p>'.T_('Family Connections has three optional sections: Family News, Prayer Concerns and Recipes.  In order to use these sections you must first add them to the site (some sections may have been previously added during installation).  Adding an optional section, allows that section to be used in the <a href="#adm-sections-nav">site navigation</a>.').'</p>
            <p>'.T_('To add an optional section:').'</p>
            <ol>
                <li>'.T_('Click the <a href="admin/config.php">Configuration</a> link on the Administration sub menu').'</li>
                <li>'.T_('Expand the <u>Sections</u> by clicking the <b>Show/Hide</b> link.').'</li>
                <li>'.T_('Click the <b>Add</b> link beside the section you want to add.').'</li>
            </ol>
            <p>'.T_('Note: If a section has been previously added it will say "Already Added" beside the section.').'</p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>

            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="adm-sections-nav">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'.T_('How do I change the site navigation?').'</b></p>
            <p>'.T_('You can only change the navigation position of a few of the sections.  They are:  Family News, Prayer Concerns, Recipes and Calendar.  The navigation is broken down into two parts, (1) the Top Navigation and the (2) the Side Navigation.  The Top Navigation can hold links for up to 6 sections.').'</p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>

            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="adm-protect-photos">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'.T_('How do I protect my photos from un-authorized users?').'</b></p>
            <p>'.T_('In all versions of Family Connections prior to 3.0, the photos in the Photo Gallery are viewable to users outside of your website, without being logged in. A non-authorized user would have to guess the location of these photos, but they still could see them, if they guessed correctly.').'</p>
            <p>'.T_('To fix this, FCMS 3.0 added a way to hide the photos from outside users.  To do this:').'</p>
            <ol>
                <li>
                    '.T_('Edit the inc/config_inc.php file on your server. Add the following line just below the MySQL database information, but above the ?>:').'
                    <br/><br/>
                    <code>define(\'UPLOADS\', \'/path/outside/of/www/uploads/\');</code>
                </li>
                <li>'.T_('Move the uploads directoy to the path you specified in step 1.').'</li>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'.T_('Back to Top').'</a></div>';
        $this->displayFooter();
    }
}
