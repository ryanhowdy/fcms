        <?php if (isset($TMPL['error'])): ?>
            <div class="info-alert">
                <h2><?php echo $TMPL['error']['header']; ?></h2>
            <?php foreach ($TMPL['error']['errors'] as $e): ?>
                <p><?php echo $e; ?></p>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($TMPL['error']['showForm']) || !isset($TMPL['error'])): ?>
            <p id="back">
                <a href="<?php echo $TMPL['backUrl']; ?>"><?php echo T_('Back to Calendar'); ?></a>
            </p>
            <div id="event_details">
            <?php if (isset($TMPL['edit'])): ?>
                <span><a href="<?php echo $TMPL['editUrl']; ?>" class="edit_event"><?php echo T_('Edit'); ?></a></span>
            <?php endif; ?>
                <h1><?php echo $TMPL['title']; ?></h1>
            <?php if (isset($TMPL['category'])): ?>
                <h2><?php echo $TMPL['category']; ?></h2>
            <?php endif; ?>
                <p id="desc"><?php echo $TMPL['description']; ?></p>
                <div id="when">
                    <h3><?php echo T_('When'); ?></h3>
                    <p>
                        <b><?php echo $TMPL['date']; ?></b>
                    <?php if (isset($TMPL['edit'])): ?>
                        <br/><?php echo $TMPL['time']; ?>
                    <?php endif; ?>
                    </p>
                    <h3><?php echo $TMPL['hostOrCreatedTitle']; ?></h3>
                    <p><?php echo $TMPL['createdBy']; ?></p>
                </div>
            </div>
        <?php endif; ?>
