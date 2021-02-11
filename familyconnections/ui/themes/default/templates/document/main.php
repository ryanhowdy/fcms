
    <?php if (isset($TMPL['pageNavigation'])) { ?>
        <?php require_once TEMPLATES.'global/page-navigation.php'; ?>
    <?php } ?>

    <?php if (isset($TMPL['documents'])) { ?>

            <script type="text/javascript" src="ui/js/tablesorter/js/jquery.tablesorter.js"></script>
            <table id="docs" class="sortable">
                <thead>
                    <tr>
                        <th class="sortfirstasc"><?php echo $TMPL['documentText']; ?></th>
                        <th><?php echo $TMPL['descriptionText']; ?></th>
                        <th><?php echo $TMPL['uploadedByText']; ?></th>
                        <th><?php echo $TMPL['dateAddedText']; ?></th>
                    </tr>
                </thead>
                <tbody>

        <?php foreach ($TMPL['documents'] as $d) { ?>
                    <tr>
                        <td>
                            <a href="?download=<?php echo $d['name']; ?>"><?php echo $d['name']; ?></a>
                            <?php if (isset($d['delete'])) { ?><?php echo $d['delete']; ?><?php } ?>
                        </td>
                        <td><?php echo $d['description']; ?></td>
                        <td><?php echo $d['user']; ?></td>
                        <td><?php echo $d['date']; ?></td>
                    </tr>
        <?php } ?>

                </tbody>
            </table>

    <?php } else { ?>

            <div class="blank-state">
                <h2><?php echo $TMPL['blankStateHeaderText']; ?></h2>
                <h3><?php echo $TMPL['blankStateText']; ?></h3>
                <h3><a href="?adddoc=yes"><?php echo $TMPL['blankStateLinkText']; ?></a></h3>
            </div>

    <?php } ?>
