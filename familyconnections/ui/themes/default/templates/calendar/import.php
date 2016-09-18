
            <form enctype="multipart/form-data" method="post" action="calendar.php">
                <fieldset class="add-edit big">
                    <legend>
                        <span><?php echo T_('Import'); ?></span>
                    </legend>
                    <p>
                        <input class="frm_file" type="file" id="file" name="file"/>
                    </p>
                    <p>
                        <input type="submit" class="sub1" name="import" value="<?php echo T_('Import'); ?>"/> 
                        <?php echo T_('or'); ?> &nbsp;
                        <a href="calendar.php"><?php echo T_('Cancel'); ?></a>
                    </p>
                </fieldset>
            </form>
