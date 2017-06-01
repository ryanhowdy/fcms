
            <script type="text/javascript" src="ui/js/livevalidation.js"></script>
            <form method="post" enctype="multipart/form-data" name="addform" action="documents.php">
                <fieldset>
                    <legend><span><?php echo $TMPL['uploadDocumentText']; ?></span></legend>
                    <p>
                        <label for="doc"><?php echo $TMPL['documentText']; ?></label>: 
                        <input type="file" name="doc" id="doc" size="30"/>
                    </p>
                    <p>
                        <label for="desc"><?php echo $TMPL['descriptionText']; ?></label>: 
                        <input type="text" name="desc" id="desc" size="60"/>
                    </p>
                    <script type="text/javascript">
                        var fdesc = new LiveValidation('desc', { onlyOnSubmit: true});
                        fdesc.add(Validate.Presence, {failureMessage: "<?php echo $TMPL['descriptionFailureText']; ?>"});
                    </script>
                    <p>
                        <input class="sub1" type="submit" name="submitadd" value="<?php echo $TMPL['addText']; ?>"/> &nbsp;
                        <a href="documents.php"><?php echo $TMPL['cancelText']; ?></a>
                    </p>
                </fieldset>
            </form>
