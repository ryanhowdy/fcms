<?php
session_start();
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');

// Check that the user is logged in
isLoggedIn();
$current_user_id = (int)escape_string($_SESSION['login_id']);

header("Cache-control: private");
// Setup the Template variables;
$TMPL['pagetitle'] = _('Help');
$TMPL['path'] = "";
$TMPL['admin_path'] = "admin/";

// Show Header
include_once(getTheme($current_user_id) . 'header.php');

echo '
        <div id="help" class="centercontent">
            <br/>
            <h4>'._('Photo Gallery').'</h4>
            <p><a href="#gallery-howworks">'._('How does the Photo Gallery work?').'</a></p>
            <p><a href="#gallery-addphoto">'._('How do I add a photo?').'</a></p>
            <p><a href="#gallery-chgphoto">'._('How do I edit/change a photo?').'</a></p>
            <p><a href="#gallery-delphoto">'._('How do I delete a photo?').'</a></p>
            <p><a href="#gallery-addcat">'._('How do I add a category?').'</a></p>
            <p><a href="#gallery-chgcat">'._('How do I rename a category?').'</a></p>
            <p><a href="#gallery-delcat">'._('How do I delete a category?').'</a></p>
            <p>&nbsp;</p>
            <h4>'._('Personal Settings').'</h4>
            <p><a href="#settings-avatar">'._('How do I add/change my avatar?').'</a></p>
            <p><a href="#settings-theme">'._('How do I change my theme?').'</a></p>
            <p><a href="#settings-password">'._('How do I change my password?').'</a></p>
            <p>&nbsp;</p>
            <h4>'._('Address Book').'</h4>
            <p><a href="#address-massemail">'._('How do I email multiple people (Mass Email)?').'</a></p>
            <p>&nbsp;</p>
            <h4>'._('Administration').'</h4>
            <p><a href="#adm-access">'._('Member Access Levels').'</a></p>
            <p><a href="#adm-sections-add">'._('How do I add an optional section?').'</a></p>
            <p><a href="#adm-sections-nav">'._('How do I change the site navigation?').'</a></p>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="gallery-howworks">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'._('How does the Photo Gallery work?').'</b></p>
            <p>'._('Each member of the website has his/her own Category on the Photo Gallery.  This category will not show up until that member creates a new sub-category and uploads at least one photo.  You can not upload photos until you have created a category.  It is best to create a new category each time you upload a new group of photos.  This helps create a more organized Photo Gallery.').'</p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'._('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="gallery-addphoto">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'._('How do I add a photo?').'</b></p>
            <ol>
            <li>'._('Choose <a href="gallery/index.php?action=upload">Upload Photos</a> from the <a href="gallery/index.php">Photo Gallery</a> menu.').'</li>
            <li>'._('Choose a category from the drop down menu.').'<br/>
                <small>'._('Note: You must have at least one category to upload photos. If you do not have a existing category you must add a category first.').'</small>
            </li>
            <li>'._('Click the browse button to browse your computer for the desired photo to upload.').'<br/>'._('If the photo you are uploading needs rotated click \'Upload Options\' and two radio buttons will drop in above the photo caption.').'<br/>
                <small>'._('Note: You must have JavaScript enabled to use the rotation feature.').'</small>
            </li>
            <li>'._('Fill in the caption (description of the photo).').'</li>
            <li>'._('Click the <b>Add Photos</b> button.').'</li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'._('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="gallery-chgphoto">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'._('How do I edit/change a photo?').'</b></p>
            <p>'._('You can only edit/change the photo\'s caption and category.').'</p>
            <ol>
            <li>'._('Navigate to the photo you would like to edit.').'</li>
            <li>'._('Click the edit button <img src="themes/default/images/edit.gif"/> located above the photo and to the right.').'</li>
            <li>'._('To change the category: choose the new category from the dropdown menu above the photo.').'<br/>'._('To edit/change the caption: make your changes in the text field area below the photo.').'</li>
            <li>'._('Click the submit changes button to finish your changes.').'</li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'._('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="gallery-delphoto">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'._('How do I delete a photo?').'</b></p>
            <ol>
                <li>'._('Navigate to the photo you would like to edit.').'</li>
                <li>'._('Click the delete button <img src="themes/default/images/delete.gif"/> located above the photo and to the right.').'</li>
                <li>'._('You will be prompted with a message asking if you are sure you want to delete that photo, click Ok.').'</li>
            </ol>
            <p>
                <small>'._('Note: you can only delete your own photos.  Once you delete a photo it is gone forever, you cannot undo a delete.').'</small>
            </p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'._('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="gallery-addcat">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'._('How do I add a category?').'</b></p>
            <ol>
                <li>'._('Choose <a href="gallery/index.php?action=category">Create/Edit Category</a> from the <a href="gallery/index.php">Photo Gallery</a> menu.').'</li>
                <li>'._('Fill out the category name.').'</li>
                <li>'._('Click the <b>Add Category</b> button.').'</li>
            </ol>
            <p>'._('A list of previously created categories will be listed below.').'</p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'._('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="gallery-chgcat">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'._('How do I rename a category?').'</b></p>
            <ol>
                <li>'._('Choose <a href="gallery/index.php?action=category">Create/Edit Category</a> from the <a href="gallery/index.php">Photo Gallery</a> menu.').'</li>
                <li>'._('Scroll down to the list of categories and find the one you want to change.').'</li>
                <li>'._('Make the desired change.').'</li>
                <li>'._('Click the edit button <img src="themes/default/images/edit.gif"/> located to the right of the category name.').'</li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'._('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="gallery-delcat">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'._('How do I delete a category?').'</b></p>
            <ol>
                <li>'._('Choose <a href="gallery/index.php?action=category">Create/Edit Category</a> from the <a href="gallery/index.php">Photo Gallery</a> menu.').'</li>
                <li>'._('Scroll down to the list of categories and find the one you want to delete.').'</li>
                <li>'._('Click the delete button <img src="themes/default/images/delete.gif"/> located to the right of the category name.').'</li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'._('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="settings-avatar">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'._('How do I add/change my avatar?').'</b></p>
            <p>'._('An avatar is just a graphical representation of a person.  You can upload a picture of yourself or any picture that you feel represents you.').'</p>
            <ol>
                <li>'._('Click on the <a href="settings.php">My Settings</a> link in the top right hand corner of the site.').'</li>
                <li>'._('Click the browse button, which will pop open a menu allowing you to search your computer for an avatar.  (Avatar\'s must be one of the following file types .jpg, .jpeg .gif or .bmp or .png)').'</li>
                <li>'._('Once you have choosen your avatar, scroll down to the bottom of the Settings page and click the Submit button.').'</li>
            </ol>
            <p>'._('Note: You can upload animated avatar\'s as long as the are smaller than 80 pixels x 80 pixels.  Uploading an avatar larger than this will result in the loss of animation.').'</p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'._('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="settings-theme">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'._('How do I change my theme?').'</b></p>
            <ol>
                <li>'._('Click on the <a href="settings.php">My Settings</a> link in the top right hand corner of the site.').'</li>
                <li>'._('Choose your theme from the drop down menu.').'</li>
                <li>'._('Scroll down to the bottom of the Settings page and click the Submit button.').'</li>
                <li>'._('Click continue and your theme will automatically be applied to the site.').'</li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'._('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="settings-password">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'._('How do I change my password?').'</b></p>
            <ol>
                <li>'._('Click on the <a href="settings.php">My Settings</a> link in the top right hand corner of the site.').'</li>
                <li>'._('Type in your new password.').'</li>
                <li>'._('Click the Submit button.').'</li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'._('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="address-massemail">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'._('How do I email multiple people (Mass Email)?').'</b></p>
            <ol>
                <li>'._('Check the checkboxes to the right of the email addresses of the members you want to email.').'</li>
                <li>'._('Click the <b>Email</b> button at the bottom right hand corner of the address book.').'</li>
                <li>'._('Fill out the email form (similar to the contact form) and click <b>Send Mass Email</b>.').'</li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'._('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="adm-access">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'._('Member Access Levels').'</b></p>
            <p>'._('Family Connections has ten different member access levels.  These levels are meant to limit the amount of access each family member has to the website.').'</p>
            <ol>
                <li><b>'._('Admin').'</b> - '._('this is the access level given to the account that was setup during the installation of FCMS. This is the only level that has the ability to change other members access levels. This level can add, update and delete all information on the site.').'</li>
                <li><b>'._('Helper').'</b> - '._('this access level has all the same priveleges of the Member level, but can also run the latest awards, can add, update and delete poll questions, and add, update and delete message board posts.').'</li>
                <li><b>'._('Member (default)').'</b> - '._('this access level can add, update and delete all information they have contributed to the site. They have view only access to other member\'s information.').'</li>
                <li><b>'._('Non-Photographer').'</b> - '._('this access level has all the same priveleges of the Member level, but cannot add, update or delete photos from the Photo Gallery.').'</li>
                <li><b>'._('Non-Poster').'</b> - '._('this access level has all the same priveleges of the Member level, but cannot add, update or delete posts from the Message Board.').'</li>
                <li><b>'._('Commenter').'</b> - '._('this access level can only add comments to Photos, Family News and can reply to posts on the Message Board.  Has view access to all other sections.').'</li>
                <li><b>'._('Poster').'</b> - '._('this access level can add, update and delete their own Message Board posts only.  Has view access to all other sections.').'</li>
                <li><b>'._('Photographer').'</b> - '._('this access level can add, update and delete their own Photos only.  Has view access to all other sections.').'</li>
                <li><b>'._('Blogger').'</b> - '._('this access level can add, update and delete their own Family News entries only.  Has view access to all other sections.').'</li>
                <li><b>'._('Guest').'</b> - '._('this access level has view only access to the site.').'</li>
            </ol>
            <br/>
            <table class="mem-access" cellpadding="0" cellspacing="0">
                <thead>
                    <tr><th rowspan="2">'._('Access Level').'</th><th colspan="6">'._('Access Rights').'</th></tr>
                    <tr>
                        <th>'._('Admininstration').'</th>
                        <th>'._('Photo Gallery').'</th>
                        <th>'._('Message Board').'</th>
                        <th>'._('Address Book').'</th>
                        <th>'._('Family News').'</th>
                        <th>'._('Prayer Concerns').'</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="level_name">'._('1. Admin').'</td>
                        <td class="y">'._('Yes').'*</td>
                        <td class="y">'._('Yes').'*</td>
                        <td class="y">'._('Yes').'*</td>
                        <td class="y">'._('Yes').'*</td>
                        <td class="y">'._('Yes').'*</td>
                        <td class="y">'._('Yes').'*</td>
                    </tr>
                    <tr>
                        <td class="level_name">'._('2. Helper').'</td>
                        <td class="y">'._('Yes').'^</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="y">'._('Yes').'</td>
                    </tr>
                    <tr>
                        <td class="level_name">'._('3. Member').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="y">'._('Yes').'</td>
                    </tr>
                    <tr>
                        <td class="level_name">'._('4. Non-Photographer').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="y">'._('Yes').'</td>
                    </tr>
                    <tr>
                        <td class="level_name">'._('5. Non-Poster').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="y">'._('Yes').'</td>
                    </tr>
                    <tr>
                        <td class="level_name">'._('6. Commenter').'</td>
                        <td class="n">'._('No').'</td>
                        <td>'._('Comment Only').'</td>
                        <td>'._('Comment Only').'</td>
                        <td class="n">'._('No').'</td>
                        <td>'._('Comment Only').'</td>
                        <td class="n">'._('No').'</td>
                    </tr>
                    <tr>
                        <td class="level_name">'._('7. Poster').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="n">'._('No').'</td>
                    </tr>
                    <tr>
                        <td class="level_name">'._('8. Photographer').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="n">'._('No').'</td>
                    </tr>
                    <tr>
                        <td class="level_name">'._('9. Blogger').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="y">'._('Yes').'</td>
                        <td class="n">'._('No').'</td>
                    </tr>
                    <tr>
                        <td class="level_name">'._('10. Guest').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="n">'._('No').'</td>
                        <td class="n">'._('No').'</td>
                    </tr>
                </tbody>
            </table>
            <p>* '._('Can add/edit/delete all members information').'<br/>^ '._('Has limited access to Administration').'</p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'._('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="adm-sections-add">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'._('How do I add an optional section?').'</b></p>
            <p>'._('Family Connections has three optional sections: Family News, Prayer Concerns and Recipes.  In order to use these sections you must first add them to the site (some sections may have been previously added during installation).  Adding an optional section, allows that section to be used in the <a href="#adm-sections-nav">site navigation</a>.').'</p>
            <p>'._('To add an optional section:').'</p>
            <ol>
                <li>'._('Click the <a href="admin/config.php">Configuration</a> link on the Administration sub menu').'</li>
                <li>'._('Expand the <u>Sections</u> by clicking the <b>Show/Hide</b> link.').'</li>
                <li>'._('Click the <b>Add</b> link beside the section you want to add.').'</li>
            </ol>
            <p>'._('Note: If a section has been previously added it will say "Already Added" beside the section.').'</p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'._('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
            <p><a name="adm-sections-nav">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b>'._('How do I change the site navigation?').'</b></p>
            <p>'._('You can only change the navigation position of a few of the sections.  They are:  Family News, Prayer Concerns, Recipes and Calendar.  The navigation is broken down into two parts, (1) the Top Navigation and the (2) the Side Navigation.  The Top Navigation can hold links for up to 6 sections.').'</p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top">'._('Back to Top').'</a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
        </div><!-- .centercontent -->';

// Show Footer
include_once(getTheme($current_user_id) . 'footer.php'); ?>