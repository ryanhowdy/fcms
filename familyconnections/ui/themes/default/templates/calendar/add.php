
        <?php if (isset($TMPL['error'])) { ?>
            <div class="error-alert"><?php echo $TMPL['error']; ?></div>
        <?php } else { ?>

            <form id="frm" method="post" action="calendar.php">
                <fieldset>
                    <legend><span><?php echo $TMPL['date']; ?></span></legend>

                    <div id="main-cal-info">
                        <div class="field-row">
                            <div class="field-label">
                                <label for="title"><b><?php echo $TMPL['eventText']; ?></b></label>
                            </div>
                            <div class="field-widget">
                                <input type="text" id="title" name="title" size="40">
                                <script type="text/javascript">
                                    var ftitle = new LiveValidation('title', { onlyOnSubmit: true});
                                    ftitle.add(Validate.Presence, {failureMessage: ""});
                                </script>
                            </div>
                        </div>
                        <div class="field-row">
                            <div class="field-label">
                                <label for="desc"><b><?php echo $TMPL['descriptionText']; ?></b></label>
                            </div>
                            <div class="field-widget">
                                <input type="text" id="desc" name="desc" size="50">
                            </div>
                        </div>
                        <div id="time" class="field-row">
                            <div class="field-label">
                                <label for="sday"><b><?php echo $TMPL['timeText']; ?></b></label>
                            </div>
                            <div class="field-widget">
                                <select id="timestart" name="timestart">
                            <?php foreach ($TMPL['startTimes'] as $t) { ?>
                                    <option value="<?php echo $t['value']; ?>" <?php echo $t['selected']; ?>><?php echo $t['text']; ?></option>
                            <?php } ?>
                                </select> &nbsp;
                                <?php echo $TMPL['throughText']; ?> &nbsp;
                                <select id="timeend" name="timeend">
                            <?php foreach ($TMPL['endTimes'] as $t) { ?>
                                    <option value="<?php echo $t['value']; ?>" <?php echo $t['selected']; ?>><?php echo $t['text']; ?></option>
                            <?php } ?>
                                </select> &nbsp;
                                <input id="all-day" name="all-day" type="checkbox" 
                                    onclick="toggleDisable($('#timestart'), $('#timeend'))"/>
                                <label for="all-day"><?php echo $TMPL['allDayText']; ?></label> 
                            </div>
                        </div>
                    </div>

                    <div id="more-cal-info">
                        <div id="cal-details">
                            <div class="field-row">
                                <div class="field-label">
                                    <label for="category"><b><?php echo $TMPL['categoryText']; ?></b></label>
                                </div>
                                <div class="field-widget">
                                    <select id="category" name="category">
                                <?php foreach ($TMPL['categories'] as $c) { ?>
                                        <option value="<?php echo $c['value']; ?>"><?php echo $c['text']; ?></option>
                                <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="field-row">
                                <div class="field-widget">
                                    <input type="checkbox" name="repeat-yearly" id="repeat-yearly"/>
                                    <label for="repeat-yearly"><b><?php echo $TMPL['repeatYearlyText']; ?></b></label>
                                </div>
                            </div>
                            <div class="field-row">
                                <div class="field-widget">
                                    <input type="checkbox" name="private" id="private"/>
                                    <label for="private"><b><?php echo $TMPL['privateText']; ?></b></label>
                                </div>
                            </div>
                            <div class="field-row">
                                <div class="field-widget">
                                    <input type="checkbox" name="invite" id="invite"/>
                                    <label for="invite"><b><?php echo $TMPL['inviteGuestsText']; ?></b></label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p>
                        <input type="hidden" id="date" name="date" value="<?php echo $TMPL['addDate']; ?>"/> 
                        <input class="sub1" type="submit" name="add" value="<?php echo $TMPL['addText']; ?>"/> 
                        <?php echo $TMPL['orText']; ?>&nbsp;
                        <a href="<?php echo $TMPL['cancelUrl']; ?>"><?php echo $TMPL['cancelText']; ?></a>
                    </p>
                </form>
            </fieldset>
        <?php } ?>
