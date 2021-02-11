            <form method="post" action="<?php echo $TMPL['url']; ?>">
                <fieldset>
                    <legend><span><?php echo $TMPL['title']; ?></span></legend>
                    <div class="field-row">
                        <div class="field-label">
                            <label for="name"><b><?php echo T_('Name'); ?></b></label>
                        </div>
                        <div class="field-widget">
                            <input type="text" id="name" name="name" size="40" value="<?php echo $TMPL['name']; ?>">
                            <script type="text/javascript">
                                var fname = new LiveValidation('name', { onlyOnSubmit: true});
                                fname.add(Validate.Presence, {failureMessage: ""});
                            </script>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">
                            <label for="color"><b><?php echo T_('Color'); ?></b></label>
                        </div>
                        <div class="field-widget">
                            <label for="none" class="colors none">
                                <input type="radio" <?php if (isset($TMPL['none'])) { echo 'checked="checked"'; } ?> name="colors" id="none" value="none"/>
                                <?php echo T_('None'); ?>
                            </label>
                            <label for="red" class="colors red">
                                <input type="radio" <?php if (isset($TMPL['red'])) { echo 'checked="checked"'; } ?> name="colors" id="red" value="red"/>
                                <?php echo T_('Red'); ?>
                            </label>
                            <label for="orange" class="colors orange">
                                <input type="radio" <?php if (isset($TMPL['orange'])) { echo 'checked="checked"'; } ?> name="colors" id="orange" value="orange"/>
                                <?php echo T_('Orange'); ?>
                            </label>
                            <label for="yellow" class="colors yellow">
                                <input type="radio" <?php if (isset($TMPL['yellow'])) { echo 'checked="checked"'; } ?> name="colors" id="yellow" value="yellow"/>
                                <?php echo T_('Yellow'); ?>
                            </label><br/>
                            <label for="green" class="colors green">
                                <input type="radio" <?php if (isset($TMPL['green'])) { echo 'checked="checked"'; } ?> name="colors" id="green" value="green"/>
                                <?php echo T_('Green'); ?>
                            </label>
                            <label for="blue" class="colors blue">
                                <input type="radio" <?php if (isset($TMPL['blue'])) { echo 'checked="checked"'; } ?> name="colors" id="blue" value="blue"/>
                                <?php echo T_('Blue'); ?>
                            </label>
                            <label for="indigo" class="colors indigo">
                                <input type="radio" <?php if (isset($TMPL['indigo'])) { echo 'checked="checked"'; } ?> name="colors" id="indigo" value="indigo"/>
                                <?php echo T_('Indigo'); ?>
                            </label>
                            <label for="violet" class="colors violet">
                                <input type="radio" <?php if (isset($TMPL['violet'])) { echo 'checked="checked"'; } ?> name="colors" id="violet" value="violet"/>
                                <?php echo T_('Violet'); ?>
                            </label>
                        </div>
                    </div>
                <?php if (isset($TMPL['edit'])) { ?>
                    <p>
                        <input type="hidden" id="id" name="id" value="<?php echo $TMPL['id']; ?>"/> 
                        <input class="sub1" type="submit" id="editcat" name="editcat" value="<?php echo T_('Edit'); ?>"/> 
                        <input class="sub2" type="submit" id="delcat" name="delcat" value="<?php echo T_('Delete'); ?>"/>
                        <?php echo T_('or'); ?>&nbsp;
                        <a href="calendar.php"><?php echo T_('Cancel'); ?></a>
                    </p>
                <?php } else { ?>
                    <p>
                        <input class="sub1" type="submit" id="addcat" name="addcat" value="<?php echo T_('Add'); ?>"/>
                        <?php echo T_('or'); ?>&nbsp;
                        <a href="calendar.php"><?php echo T_('Cancel'); ?></a>
                    </p>
                <?php } ?>
                </fieldset>
            </form>
