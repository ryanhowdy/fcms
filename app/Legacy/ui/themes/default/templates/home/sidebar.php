            <div id="leftcolumn">

                <h2 class="calmenu"><?php echo $TMPL['textCalendar']; ?></h2>

                <table id="small-calendar">
                    <tr>
                        <th colspan="7">
                            <h3><a href="<?php $TMPL['thisMonthUrl']; ?>"><?php echo $TMPL['thisMonth']; ?></a></h3>
                        </th>
                    </tr>
                    <tr>
                <?php foreach ($TMPL['weekDays'] as $wd): ?>
                        <td class="weekDays"><?php echo $wd; ?></td>
                <?php endforeach; ?>
                    </tr>

                <?php foreach ($TMPL['days'] as $week => $days): ?>
                    <tr>
                    <?php foreach ($days as $day): ?>
                        <td class="<?php echo $day['class']; ?>"><?php echo $day['data']; ?></td>
                    <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </table>
                
                <h3><?php echo $TMPL['textUpcoming']; ?></h3>

            <?php foreach ($TMPL['events'] as $event): ?>
                <div class="events">
                    <a title="<?php echo $event['desc']; ?>" href="calendar.php?event=<?php echo $event['id']; ?>">
                        <?php echo $event['title']; ?>
                    </a><br/>
                    <?php echo $event['date']; ?>
                </div>
            <?php endforeach; ?>

            <?php if (isset($TMPL['pollId'])): ?>
                <h2 class="pollmenu"><?php echo $TMPL['textPolls']; ?></h2>
                <?php
                if (isset($TMPL['pollOptions']))
                {
                    require_once app_path().'/Legacy/ui/themes/default/templates/poll/view.php';
                }
                else
                {
                    require_once app_path().'/Legacy/ui/themes/default/templates/poll/result.php';
                } ?>
            <?php endif; ?>

                <h2 class="membermenu"><?php echo $TMPL['textMembersOnline']; ?></h2>
                <div class="membermenu">
                    <h3><?php echo $TMPL['textLastSeen']; ?>:</h3>
                    <ul class="avatar-member-list">
            <?php if (isset($TMPL['membersOnline'])): ?>
                <?php foreach ($TMPL['membersOnline'] as $member): ?>
                        <li>
                            <a href="profile.php?member=<?php echo $member['id']; ?>" class="tooltip" onmouseover="showTooltip(this)" onmouseout="hideTooltip(this)">
                                <img alt="avatar" src="<?php echo $member['avatar']; ?>"/>
                            </a>
                            <div class="tooltip" style="display:none;">
                                <h5><?php echo $member['displayname']; ?></h5>
                                <span><?php echo $member['since']; ?></span>
                            </div>
                        </li>
                <?php endforeach; ?>
            <?php endif; ?>
                    </ul>
                    <br/><br/>
                </div>

            </div><!--/#leftcolumn-->

            <div id="maincolumn">
