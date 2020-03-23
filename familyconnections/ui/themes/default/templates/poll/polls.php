            <h2><?php echo $TMPL['textPastPolls']; ?></h2>
            <table class="sortable">
                <thead>
                    <tr>
                        <th><?php echo $TMPL['textQuestion']; ?></th>
                        <th><?php echo $TMPL['textDate']; ?></th>
                        <th><?php echo $TMPL['textVotes']; ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($TMPL['polls'] as $poll) { ?>
                    <tr>
                        <td><a href="<?php echo $poll['url']; ?>"><?php echo $poll['question']; ?></a></td>
                        <td><?php echo $poll['date']; ?></td>
                        <td><?php echo $poll['vote']; ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
