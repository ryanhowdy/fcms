            <script type="text/javascript" src="ui/js/livevalidation.js"></script>
            <form method="post" id="editform" action="familynews.php">
                <fieldset>
                    <legend><span><?php echo $TMPL['editNewsText']; ?></span></legend>
                    <p>
                        <label for="title"><?php echo $TMPL['titleText']; ?></label>:
                        <input type="text" name="title" id="title" title="<?php echo $TMPL['titleOfYourFamilyNewsText']; ?>"
                            value="<?php echo $TMPL['title']; ?>" tabindex="1" size="50"/>
                    </p>
                    <script type="text/javascript">
                        var ftitle = new LiveValidation('title', { onlyOnSubmit:true });
                        ftitle.add(Validate.Presence, { failureMessage: "" });
                    </script>
                    <script type="text/javascript">var bb = new BBCode();</script>
<?php require_once(TEMPLATES.'global/bbcode-toolbar.php'); ?>
                    <div>
                        <textarea name="post" id="post" rows="10" cols="63" tabindex="2"><?php echo $TMPL['news']; ?></textarea>
                    </div>
                    <script type="text/javascript">bb.init('post');</script>
                    <p>
                        <input type="hidden" name="id" value="<?php echo $TMPL['id']; ?>"/>
                        <input type="hidden" name="user" value="<?php echo $TMPL['user']; ?>"/>
                        <input class="sub1" type="submit" name="submitedit" tabindex="3" value="<?php echo $TMPL['editText']; ?>"/>
                         &nbsp;<?php echo $TMPL['orText']; ?> &nbsp;
                        <a href="familynews.php"><?php echo $TMPL['cancelText']; ?></a>
                    </p>
                </fieldset>
            </form>
