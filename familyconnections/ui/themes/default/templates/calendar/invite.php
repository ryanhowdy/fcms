
            <form id="invite-form" method="post" action="calendar.php?event=<?php echo $TMPL['calendarId']; ?>">
                <fieldset>
                    <legend><span><?php echo T_('Choose Guests'); ?></span></legend>
                    <h3><?php echo T_('Invite Members'); ?></h3>
                    <p>
                        <input type="checkbox" id="all-members" name="all-members" value="yes"/>
                        <label for="all-members"><?php echo T_('Invite all Members?'); ?></label>
                    </p>
                    <div id="invite-members-list">
                        <table id="invite-table" cellspacing="0" cellpadding="0">
                            <thead>
                                <tr>
                                    <th class="chk"></td> 
                                    <th><?php echo T_('Name'); ?></td> 
                                    <th><?php echo T_('Email'); ?></td> 
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($TMPL['rows'] as $r): ?>
                                <tr>
                                    <td class="chk">
                                        <input type="checkbox" id="member<?php echo $r['id']; ?>" name="member[]" value="<?php echo $r['id']; ?>"/>
                                    </td>
                                    <td><?php echo $r['name']; ?></td>
                                    <td>
                                        <?php echo $r['email']; ?>
                                        <input type="hidden" name="id<?php echo $r['id']; ?>" value="<?php echo $r['email']; ?>"/>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <h3><?php echo T_('Invite Non Members'); ?></h3>
                    <span><?php echo T_('Enter list of emails to invite. One email per line.'); ?></span>
                    <textarea name="non-member-emails" id="non-member-emails" rows="10" cols="63"></textarea>
                    <p style="clear:both">
                        <input type="hidden" name="calendar" value="<?php echo $TMPL['calendarId']; ?>"/>
                        <input class="sub1" type="submit" id="submit-invite" name="submit-invite" value="<?php echo T_('Send Invitations'); ?>"/> 
                        <?php echo T_('or'); ?>&nbsp;
                        <a href="calendar.php"><?php echo T_('Cancel'); ?></a>
                    </p>
                </fieldset>
            </form>
