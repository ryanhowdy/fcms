                <form class="<?php echo $TMPL['pollFormClass']; ?>" method="post" action="polls.php">
                    <h3><?php echo $TMPL['pollQuestion']; ?></h3>
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
                <?php if (isset($TMPL['textCommentsCount'])): ?>
                    <p class="actions">
                        <a href="#comments"><?php echo $TMPL['textCommentsCount']; ?></a><br/>
                        <input type="submit" class="disabled" disabled="disabled" id="vote" name="vote" value="<?php echo $TMPL['textAlreadyVoted']; ?>"/>
                    </p>
                <?php else: ?>
                    <a href="polls.php?action=pastpolls"><?php echo $TMPL['textPastPolls']; ?></a>
                <?php endif; ?>
                </form>
