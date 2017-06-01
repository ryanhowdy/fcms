        <?php if (isset($TMPL['showAttendingForm'])): ?>
            <form action="calendar.php?event=<?php echo $TMPL['eventId']; ?>" method="post">
                <h1 id="attending_header"><?php echo T_('Are you attending?'); ?></h1>
                <ul id="attending">
                    <li>
                        <label for="yes">
                            <img src="ui/img/attend_yes.png"/><br/>
                            <b><?php echo T_('Yes'); ?></b>
                        </label>
                        <input type="radio" id="yes" name="attending" value="1"/>
                    </li>
                    <li>
                        <label for="maybe">
                            <img src="ui/img/attend_maybe.png"/><br/>
                            <b><?php echo T_('Maybe'); ?></b>
                        </label>
                        <input type="radio" id="maybe" name="attending" value="2"/>
                    </li>
                    <li>
                        <label for="no">
                            <img src="ui/img/attend_no.png"/><br/>
                            <b><?php echo T_('No'); ?></b>
                        </label>
                        <input type="radio" id="no" name="attending" value="0"/>
                    </li>
                    <li class="submit">
                        <textarea id="response" name="response" cols="50" rows="10"></textarea>
                        <input type="hidden" id="id" name="id" value="<?php echo $TMPL['currentUserResponseId']; ?>"/>
                        <input type="submit" id="attend_submit" name="attend_submit" value="<?php echo T_('Submit'); ?>"/>
                    </li>
                </ul>
            </form>';
        <?php endif; ?>

            <div id="leftcolumn">
                <div id="whos_coming">
                    <h3><?php echo T_("Who's Coming"); ?></h3>
                    <h3 class="coming">
                        <span class="ok"></span><?php echo T_('Yes'); ?> <i><?php echo $TMPL['whosComing']['yes']['count']; ?></i>
                    </h3>
                    <div class="coming_details">
                <?php if (count($TMPL['whosComing']['yes']['users']) > 0): ?>
                    <?php foreach ($TMPL['whosComing']['yes']['users'] as $u): ?>
                        <p><?php echo $u; ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>
                    </div>
                    <h3 class="coming">
                        <span class="maybe"></span><?php echo T_('Maybe'); ?> <i><?php echo $TMPL['whosComing']['maybe']['count']; ?></i>
                    </h3>
                    <div class="coming_details">
                <?php if (count($TMPL['whosComing']['maybe']['users']) > 0): ?>
                    <?php foreach ($TMPL['whosComing']['maybe']['users'] as $u): ?>
                        <p><?php echo $u; ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>
                    </div>
                    <h3 class="coming">
                        <span class="no"></span><?php echo T_('No'); ?> <i><?php echo $TMPL['whosComing']['no']['count']; ?></i>
                    </h3>
                    <div class="coming_details">
                <?php if (count($TMPL['whosComing']['no']['users']) > 0): ?>
                    <?php foreach ($TMPL['whosComing']['no']['users'] as $u): ?>
                        <p><?php echo $u; ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>
                    </div>
                    <h3 class="coming">
                        <?php echo T_('Undecided'); ?> <i><?php echo $TMPL['whosComing']['undecided']['count']; ?></i>
                    </h3>
                    <div class="coming_details">
                <?php if (count($TMPL['whosComing']['undecided']['users']) > 0): ?>
                    <?php foreach ($TMPL['whosComing']['undecided']['users'] as $u): ?>
                        <p><?php echo $u; ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="maincolumn">
            <?php foreach ($TMPL['responses'] as $r): ?>
                <div class="comment_block">
                    <img class="avatar" src="ui/img/attend_<?php echo $r['responseType']; ?>.png" alt="<?php echo $r['responseText']; ?>"/>
                    <b><?php echo $r['name']; ?></b> <i><?php echo $r['updated']; ?></i>
                    <p><?php echo $r['text']; ?></p>
                </div>
            <?php endforeach; ?>
            </div>
