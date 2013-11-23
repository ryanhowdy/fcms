                <h2 class="pollmenu"><?php echo $TMPL['textPolls']; ?></h2>
                <form class="poll-small" method="post" action="polls.php">
                    <h3><?php echo $TMPL['pollQuestion']; ?></h3>

            <?php if (isset($TMPL['pollOptions'])): ?>
                <?php foreach ($TMPL['pollOptions'] as $option): ?>
                    <p>
                        <label class="radio_label">
                            <input type="radio" name="option" value="<?php echo $option['id']; ?>"/>
                            <?php echo $option['text']; ?>
                        </label>
                    </p>
                <?php endforeach; ?>
                    <input type="hidden" id="id" name="id" value="<?php echo $TMPL['pollId']; ?>"/>
                    <p><input type="submit" id="vote" name="vote" value="<?php echo $TMPL['textPollVote']; ?>"/></p>
                    <a href="polls.php?id=<?php echo $TMPL['pollId']; ?>&amp;results=1"><?php echo $TMPL['textPollResults']; ?></a> |
                    <a href="polls.php?action=pastpolls"><?php echo $TMPL['textPastPolls']; ?></a>
            <?php else: ?>

                    <ul class="poll-results">
                <?php foreach ($TMPL['pollResults'] as $result): ?>
                        <li>
                            <b><?php echo $result['text']; ?></b>
                            <span><?php echo $result['votes']; ?></span>
                            <a href="#" onclick="$('who<?php echo $result['count']; ?>').toggle(); return false;" class="progress" title="<?php echo $result['textClick']; ?>">
                                <div class="bar" style="width:<?php echo $result['percent']; ?>%"></div>
                            </a>
                            <div id="who<?php echo $result['count']; ?>" class="who-voted" style="display:none">
                                <ul class="avatar-member-list-small">
                    <?php foreach ($result['users'] as $user): ?>
                        <?php if (is_array($user)): ?>
                                    <li>
                                        <div onmouseover="showTooltip(this)" onmouseout="hideTooltip(this)">
                                            <img src="<?php echo $user['avatar']; ?>"/>
                                        </div>
                                        <div class="tooltip" style="display:none;">
                                            <h5><?php echo $user['name']; ?></h5>
                                        </div>
                                    </li>
                        <?php else: ?>
                                    <li><?php echo $user; ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                                </ul><!--/.avatar-member-list-small-->
                            </div><!--/#whoX-->
                        </li>
                <?php endforeach; ?>
                    </ul><!--/.poll-results-->
                    <a href="polls.php?action=pastpolls"><?php echo $TMPL['textPastPolls']; ?></a>

            <?php endif; ?>

                </form>
