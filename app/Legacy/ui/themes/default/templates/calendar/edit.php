
        <?php if (isset($TMPL['error'])): ?>
            <div class="error-alert"><?php echo $TMPL['error']; ?></div>
        <?php else: ?>

            <form id="frm" method="post" action="calendar.php">
                <fieldset>
                    <legend><span><?php echo $TMPL['editEventText']; ?></span></legend>
                    <div class="field-row">
                        <div class="field-label">
                            <label for="title"><b><?php echo $TMPL['eventText']; ?></b></label>
                        </div>
                        <div class="field-widget">
                            <input type="text" id="title" name="title" size="40" 
                                value="<?php echo $TMPL['title']; ?>"/>
                            <script type="text/javascript">
                                var ftitle = new LiveValidation(\'title\', { onlyOnSubmit: true});
                                ftitle.add(Validate.Presence, {failureMessage: ""});
                            </script>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">
                            <label for="desc"><b><?php echo $TMPL['descriptionText']; ?></b></label>
                        </div>
                        <div class="field-widget">
                            <input type="text" id="desc" name="desc" size="50" 
                                value="<?php echo $TMPL['description']; ?>"/>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">
                            <label for="sday"><b><?php echo $TMPL['dateText']; ?></b></label>
                        </div>
                        <div class="field-widget">
                            <select id="sday" name="sday">
                        <?php foreach ($TMPL['days'] as $d): ?>
                                <option value="<?php echo $d['value']; ?>" <?php echo $d['selected']; ?>><?php echo $d['text']; ?></option>
                        <?php endforeach; ?>
                            </select>
                            <select id="smonth" name="smonth">
                        <?php foreach ($TMPL['months'] as $m): ?>
                                <option value="<?php echo $m['value']; ?>" <?php echo $m['selected']; ?>><?php echo $m['text']; ?></option>
                        <?php endforeach; ?>
                            </select>
                            <input type="text" id="syear" name="syear" size="4" value="<?php echo $TMPL['year']; ?>"/>
                        </div>
                    </div>
                    <div id="time" class="field-row">
                        <div class="field-label">
                            <label for="sday"><b><?php echo $TMPL['timeText']; ?></b></label>
                        </div>
                        <div class="field-widget">
                            <select id="timestart" name="timestart">
                        <?php foreach ($TMPL['startTimes'] as $t): ?>
                                <option value="<?php echo $t['value']; ?>" <?php echo $t['selected']; ?>><?php echo $t['text']; ?></option>
                        <?php endforeach; ?>
                            </select> &nbsp;
                            <?php echo $TMPL['throughText']; ?> &nbsp;
                            <select id="timeend" name="timeend">
                        <?php foreach ($TMPL['endTimes'] as $t): ?>
                                <option value="<?php echo $t['value']; ?>" <?php echo $t['selected']; ?>><?php echo $t['text']; ?></option>
                        <?php endforeach; ?>
                            </select> &nbsp;
                            <input id="all-day" named="all-day" type="checkbox" 
                                onclick="toggleDisable($('#timestart'), $('#timeend'))" <?php echo $TMPL['allDayChecked']; ?>/>
                            <label for="all-day"><?php echo $TMPL['allDayText']; ?></label> 
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">
                            <label for="category"><b><?php echo $TMPL['categoryText']; ?></b></label>
                        </div>
                        <div class="field-widget">
                            <select id="category" name="category">
                        <?php foreach ($TMPL['categories'] as $c): ?>
                                <option value="<?php echo $c['value']; ?>" <?php echo $c['selected']; ?>><?php echo $c['text']; ?></option>
                        <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">
                            <label for="repeat-yearly"><b><?php echo $TMPL['repeatYearlyText']; ?></b></label>
                        </div>
                        <div class="field-widget">
                            <input type="checkbox" name="repeat-yearly" id="repeat-yearly" <?php echo $TMPL['repeatChecked']; ?>/>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">
                            <label for="private"><b><?php echo $TMPL['privateText']; ?></b></label>
                        </div>
                        <div class="field-widget">
                            <input type="checkbox" name="private" id="private" <?php echo $TMPL['privateChecked']; ?>/>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">
                            <label for="invite"><b><?php echo $TMPL['inviteGuestsText']; ?></b></label>
                        </div>
                        <div class="field-widget">
                            <input type="checkbox" name="invite" id="invite" <?php echo $TMPL['inviteChecked']; ?>/>
                        </div>
                    </div>

                    <p>
                        <input type="hidden" name="id" value="<?php echo $TMPL['id']; ?>"/>
                        <input class="sub1" type="submit" name="edit" value="<?php echo $TMPL['editText']; ?>"/> 
                        <input class="sub2" type="submit" id="delcal" name="delete" value="<?php echo $TMPL['deleteText']; ?>"/>
                        <?php echo $TMPL['orText']; ?>&nbsp;
                        <a href="<?php echo $TMPL['cancelUrl']; ?>"><?php echo $TMPL['cancelText']; ?></a>
                    </p>
                </fieldset>
            </form>
        <?php endif; ?>
