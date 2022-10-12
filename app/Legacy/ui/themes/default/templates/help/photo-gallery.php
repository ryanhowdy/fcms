
        <div id="maincolumn">
            <h4><?php echo T_('Photo Gallery'); ?></h4>
            <p><a href="#gallery-howworks"><?php echo T_('How does the Photo Gallery work?'); ?></a></p>
            <p><a href="#gallery-addphoto"><?php echo T_('How do I add a photo?'); ?></a></p>
            <p><a href="#gallery-chgphoto"><?php echo T_('How do I edit/change a photo?'); ?></a></p>
            <p><a href="#gallery-delphoto"><?php echo T_('How do I delete a photo?'); ?></a></p>
            <p><a href="#gallery-addcat"><?php echo T_('How do I add a category?'); ?></a></p>
            <p><a href="#gallery-chgcat"><?php echo T_('How do I rename a category?'); ?></a></p>
            <p><a href="#gallery-delcat"><?php echo T_('How do I delete a category?'); ?></a></p>

            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="gallery-howworks">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b><?php echo T_('How does the Photo Gallery work?'); ?></b></p>
            <p><?php echo T_('Each member of the website has his/her own Category on the Photo Gallery.  This category will not show up until that member creates a new sub-category and uploads at least one photo.  You can not upload photos until you have created a category.  It is best to create a new category each time you upload a new group of photos.  This helps create a more organized Photo Gallery.'); ?></p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top"><?php echo T_('Back to Top'); ?></a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="gallery-addphoto">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b><?php echo T_('How do I add a photo?'); ?></b></p>
            <ol>
            <li><?php echo T_('Choose <a href="gallery/index.php?action=upload">Upload Photos</a> from the <a href="gallery/index.php">Photo Gallery</a> menu.'); ?></li>
            <li><?php echo T_('Choose a category from the drop down menu.'); ?><br/>
                <small><?php echo T_('Note: You must have at least one category to upload photos. If you do not have a existing category you must add a category first.'); ?></small>
            </li>
            <li><?php echo T_('Click the browse button to browse your computer for the desired photo to upload.'); ?><br/><?php echo T_('If the photo you are uploading needs rotated click \'Upload Options\' and two radio buttons will drop in above the photo caption.'); ?><br/>
                <small><?php echo T_('Note: You must have JavaScript enabled to use the rotation feature.'); ?></small>
            </li>
            <li><?php echo T_('Fill in the caption (description of the photo).'); ?></li>
            <li><?php echo T_('Click the <b>Add Photos</b> button.'); ?></li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top"><?php echo T_('Back to Top'); ?></a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="gallery-chgphoto">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b><?php echo T_('How do I edit/change a photo?'); ?></b></p>
            <p><?php echo T_('You can only edit/change the photo\'s caption and category.'); ?></p>
            <ol>
            <li><?php echo T_('Navigate to the photo you would like to edit.'); ?></li>
            <li><?php echo T_('Click the edit button <img src="ui/themes/default/img/edit.gif"/> located above the photo and to the right.'); ?></li>
            <li><?php echo T_('To change the category: choose the new category from the dropdown menu above the photo.'); ?><br/><?php echo T_('To edit/change the caption: make your changes in the text field area below the photo.'); ?></li>
            <li><?php echo T_('Click the submit changes button to finish your changes.'); ?></li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top"><?php echo T_('Back to Top'); ?></a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="gallery-delphoto">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b><?php echo T_('How do I delete a photo?'); ?></b></p>
            <ol>
                <li><?php echo T_('Navigate to the photo you would like to edit.'); ?></li>
                <li><?php echo T_('Click the delete button <img src="ui/themes/default/img/delete.gif"/> located above the photo and to the right.'); ?></li>
                <li><?php echo T_('You will be prompted with a message asking if you are sure you want to delete that photo, click Ok.'); ?></li>
            </ol>
            <p>
                <small><?php echo T_('Note: you can only delete your own photos.  Once you delete a photo it is gone forever, you cannot undo a delete.'); ?></small>
            </p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top"><?php echo T_('Back to Top'); ?></a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="gallery-addcat">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b><?php echo T_('How do I add a category?'); ?></b></p>
            <ol>
                <li><?php echo T_('Choose <a href="gallery/index.php?action=category">Create/Edit Category</a> from the <a href="gallery/index.php">Photo Gallery</a> menu.'); ?></li>
                <li><?php echo T_('Fill out the category name.'); ?></li>
                <li><?php echo T_('Click the <b>Add Category</b> button.'); ?></li>
            </ol>
            <p><?php echo T_('A list of previously created categories will be listed below.'); ?></p>
            <p>&nbsp;</p>
            <div class="top"><a href="#top"><?php echo T_('Back to Top'); ?></a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="gallery-chgcat">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b><?php echo T_('How do I rename a category?'); ?></b></p>
            <ol>
                <li><?php echo T_('Choose <a href="gallery/index.php?action=category">Create/Edit Category</a> from the <a href="gallery/index.php">Photo Gallery</a> menu.'); ?></li>
                <li><?php echo T_('Scroll down to the list of categories and find the one you want to change.'); ?></li>
                <li><?php echo T_('Make the desired change.'); ?></li>
                <li><?php echo T_('Click the edit button <img src="ui/themes/default/img/edit.gif"/> located to the right of the category name.'); ?></li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top"><?php echo T_('Back to Top'); ?></a></div>
            <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><hr/><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

            <p><a name="gallery-delcat">&nbsp;</a></p>
            <p>&nbsp;</p>
            <p><b><?php echo T_('How do I delete a category?'); ?></b></p>
            <ol>
                <li><?php echo T_('Choose <a href="gallery/index.php?action=category">Create/Edit Category</a> from the <a href="gallery/index.php">Photo Gallery</a> menu.'); ?></li>
                <li><?php echo T_('Scroll down to the list of categories and find the one you want to delete.'); ?></li>
                <li><?php echo T_('Click the delete button <img src="ui/themes/default/img/delete.gif"/> located to the right of the category name.'); ?></li>
            </ol>
            <p>&nbsp;</p>
            <div class="top"><a href="#top"><?php echo T_('Back to Top'); ?></a></div>';
        </div>
