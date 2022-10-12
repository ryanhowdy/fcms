
        <div id="maincolumn">
            <h4><?php echo T_('Administration'); ?></h4>

            <p><a href="#adm-access"><?php echo T_('Member Access Levels'); ?></a></p>
            <p><a href="#adm-sections-add"><?php echo T_('How do I add an optional section?'); ?></a></p>
            <p><a href="#adm-sections-nav"><?php echo T_('How do I change the site navigation?'); ?></a></p>
            <p><a href="#adm-protect-photos"><?php echo T_('How do I protect my photos from un-authorized users?'); ?></a></p>

            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="adm-access">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b><?php echo T_('Member Access Levels'); ?></b></p>
            <p><?php echo T_('Family Connections has ten different member access levels.  These levels are meant to limit the amount of access each family member has to the website.'); ?></p>
            <ol>
                <li><b><?php echo T_('Admin'); ?></b> - <?php echo T_('this is the access level given to the account that was setup during the installation of FCMS. This is the only level that has the ability to change other members access levels. This level can add, update and delete all information on the site.'); ?></li>
                <li><b><?php echo T_('Helper'); ?></b> - <?php echo T_('this access level has all the same priveleges of the Member level, but can also run the latest awards, can add, update and delete poll questions, and add, update and delete message board posts.'); ?></li>
                <li><b><?php echo T_('Member (default)'); ?></b> - <?php echo T_('this access level can add, update and delete all information they have contributed to the site. They have view only access to other member\'s information.'); ?></li>
                <li><b><?php echo T_('Non-Photographer'); ?></b> - <?php echo T_('this access level has all the same priveleges of the Member level, but cannot add, update or delete photos from the Photo Gallery.'); ?></li>
                <li><b><?php echo T_('Non-Poster'); ?></b> - <?php echo T_('this access level has all the same priveleges of the Member level, but cannot add, update or delete posts from the Message Board.'); ?></li>
                <li><b><?php echo T_('Commenter'); ?></b> - <?php echo T_('this access level can only add comments to Photos, Family News and can reply to posts on the Message Board.  Has view access to all other sections.'); ?></li>
                <li><b><?php echo T_('Poster'); ?></b> - <?php echo T_('this access level can add, update and delete their own Message Board posts only.  Has view access to all other sections.'); ?></li>
                <li><b><?php echo T_('Photographer'); ?></b> - <?php echo T_('this access level can add, update and delete their own Photos only.  Has view access to all other sections.'); ?></li>
                <li><b><?php echo T_('Blogger'); ?></b> - <?php echo T_('this access level can add, update and delete their own Family News entries only.  Has view access to all other sections.'); ?></li>
                <li><b><?php echo T_('Guest'); ?></b> - <?php echo T_('this access level has view only access to the site.'); ?></li>
            </ol>
            <br/>
            <table class="mem-access" cellpadding="0" cellspacing="0">
                <thead>
                    <tr><th rowspan="2"><?php echo T_('Access Level'); ?></th><th colspan="6"><?php echo T_('Access Rights'); ?></th></tr>
                    <tr>
                        <th><?php echo T_('Admininstration'); ?></th>
                        <th><?php echo T_('Photo Gallery'); ?></th>
                        <th><?php echo T_('Message Board'); ?></th>
                        <th><?php echo T_('Address Book'); ?></th>
                        <th><?php echo T_('Family News'); ?></th>
                        <th><?php echo T_('Prayer Concerns'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="level_name"><?php echo T_('1. Admin'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?>*</td>
                        <td class="y"><?php echo T_('Yes'); ?>*</td>
                        <td class="y"><?php echo T_('Yes'); ?>*</td>
                        <td class="y"><?php echo T_('Yes'); ?>*</td>
                        <td class="y"><?php echo T_('Yes'); ?>*</td>
                        <td class="y"><?php echo T_('Yes'); ?>*</td>
                    </tr>
                    <tr>
                        <td class="level_name"><?php echo T_('2. Helper'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?>^</td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                    </tr>
                    <tr>
                        <td class="level_name"><?php echo T_('3. Member'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                    </tr>
                    <tr>
                        <td class="level_name"><?php echo T_('4. Non-Photographer'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                    </tr>
                    <tr>
                        <td class="level_name"><?php echo T_('5. Non-Poster'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                    </tr>
                    <tr>
                        <td class="level_name"><?php echo T_('6. Commenter'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td><?php echo T_('Comment Only'); ?></td>
                        <td><?php echo T_('Comment Only'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td><?php echo T_('Comment Only'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                    </tr>
                    <tr>
                        <td class="level_name"><?php echo T_('7. Poster'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                    </tr>
                    <tr>
                        <td class="level_name"><?php echo T_('8. Photographer'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                    </tr>
                    <tr>
                        <td class="level_name"><?php echo T_('9. Blogger'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="y"><?php echo T_('Yes'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                    </tr>
                    <tr>
                        <td class="level_name"><?php echo T_('10. Guest'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                        <td class="n"><?php echo T_('No'); ?></td>
                    </tr>
                </tbody>
            </table>
            <p>* <?php echo T_('Can add/edit/delete all members information'); ?><br/>^ <?php echo T_('Has limited access to Administration'); ?></p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top"><?php echo T_('Back to Top'); ?></a></div>

            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="adm-sections-add">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b><?php echo T_('How do I add an optional section?'); ?></b></p>
            <p><?php echo T_('Family Connections has three optional sections: Family News, Prayer Concerns and Recipes.  In order to use these sections you must first add them to the site (some sections may have been previously added during installation).  Adding an optional section, allows that section to be used in the <a href="#adm-sections-nav">site navigation</a>.'); ?></p>
            <p><?php echo T_('To add an optional section:'); ?></p>
            <ol>
                <li><?php echo T_('Click the <a href="admin/config.php">Configuration</a> link on the Administration sub menu'); ?></li>
                <li><?php echo T_('Expand the <u>Sections</u> by clicking the <b>Show/Hide</b> link.'); ?></li>
                <li><?php echo T_('Click the <b>Add</b> link beside the section you want to add.'); ?></li>
            </ol>
            <p><?php echo T_('Note: If a section has been previously added it will say "Already Added" beside the section.'); ?></p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top"><?php echo T_('Back to Top'); ?></a></div>

            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="adm-sections-nav">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b><?php echo T_('How do I change the site navigation?'); ?></b></p>
            <p><?php echo T_('You can only change the navigation position of a few of the sections.  They are:  Family News, Prayer Concerns, Recipes and Calendar.  The navigation is broken down into two parts, (1) the Top Navigation and the (2) the Side Navigation.  The Top Navigation can hold links for up to 6 sections.'); ?></p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top"><?php echo T_('Back to Top'); ?></a></div>

            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="adm-protect-photos">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b><?php echo T_('How do I protect my photos from un-authorized users?'); ?></b></p>
            <p><?php echo T_('In all versions of Family Connections prior to 3.0, the photos in the Photo Gallery are viewable to users outside of your website, without being logged in. A non-authorized user would have to guess the location of these photos, but they still could see them, if they guessed correctly.'); ?></p>
            <p><?php echo T_('To fix this, FCMS 3.0 added a way to hide the photos from outside users.  To do this:'); ?></p>
            <ol>
                <li>
                    <?php echo T_('Edit the inc/config_inc.php file on your server. Add the following line just below the MySQL database information, but above the ?>:'); ?>
                    <br/><br/>
                    <code>define(\'UPLOADS\', \'/path/outside/of/www/uploads/\');</code>
                </li>
                <li><?php echo T_('Move the uploads directoy to the path you specified in step 1.'); ?></li>
            <p>&nbsp;</p>
            <div class="top"><a href="#top"><?php echo T_('Back to Top'); ?></a></div>';
        </div>
