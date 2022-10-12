
            <fieldset>
                <form method="post" class="contactform" action="contact.php">
                    <div class="field-row">
                        <div class="field-label">
                            <label for="email"><b><?php echo $TMPL['emailText']; ?></b></label>
                        </div>
                        <div class="field-widget">
                            <input type="text" id="email" name="email" size="30" value="<?php echo $TMPL['email']; ?>"/>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">
                            <label for="name"><b><?php echo $TMPL['nameText']; ?></b></label>
                        </div>
                        <div class="field-widget">
                            <input type="text" id="name" name="name" size="30" value="<?php echo $TMPL['name']; ?>"/>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">
                            <label for="subject"><b><?php echo $TMPL['subjectText']; ?></b></label>
                        </div>
                        <div class="field-widget">
                            <input type="text" id="subject" name="subject" size="30" value="<?php echo $TMPL['subject']; ?>"/>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">
                            <label for="msg"><b><?php echo $TMPL['messageText']; ?></b></label>
                        </div>
                        <div class="field-widget">
                            <textarea name="msg" rows="10" cols="40"><?php echo $TMPL['message']; ?></textarea>
                        </div>
                    </div>
                    <p><input type="submit" name="submit" class="sub1" value="<?php echo $TMPL['submitText']; ?>"/></p>
                </form>
            </fieldset>
