
        <div id="sections_menu">
            <ul>
                <li><a href="?view=<?php echo $TMPL['familyTreeId']; ?>"><?php echo T_('View Family Tree'); ?></a></li>
            </ul>
        </div>
        <div id="actions_menu">
            <ul class="tools">
            <?php if ($TMPL['canEdit']): ?>
                <li><a href="?edit=<?php echo $TMPL['familyTreeId']; ?>"><?php echo T_('Edit This Person'); ?></a></li>
            <?php endif; ?>
                <span class="tools">
                        <li><a class="add" href="#<?php echo $TMPL['familyTreeId']; ?>"><?php echo T_('Add Family Member'); ?></a></li>
                </span>
            </ul>
        </div>
        <div class="person-details">
            <img class="avatar" src="<?php echo $TMPL['avatarPath']; ?>"/>
            <h1><?php echo $TMPL['name']; ?></h1>
            <p class="member_status"><?php echo $TMPL['status']; ?></p>
        </div>
        <p>
            <?php echo $TMPL['dateOfBirth']; ?><br/>
            <?php echo $TMPL['dateOfDeath']; ?>
        </p>
        <h3><?php echo T_('Bio'); ?></h3>
        <p>
    <?php if (isset($TMPL['bio'])): ?>
        <?php echo $TMPL['bio']; ?>
    <?php else: ?>
        <?php if ($TMPL['canEdit']): ?>
        <a href="?edit=<?php echo $TMPL['familyTreeId']; ?>"><?php echo $TMPL['noBio']; ?></a>
        <?php else: ?>
        <?php echo $TMPL['noBio']; ?>
        <?php endif; ?>
    <?php endif; ?>
        </p>
        <h3><?php echo T_('Immediate Family'); ?></h3>
        <ul id="immediate-family">
    <?php foreach ($TMPL['relatives'] as $relative): ?>
            <li>
                <img class="small-avatar" src="<?php echo $relative['avatar']; ?>"/>
                <p>
                    <a href="?details=<?php echo $relative['id']; ?>"><?php echo $relative['name']; ?></a>
                    <i><?php echo $relative['relation']; ?></i>
                </p>
            </li>
    <?php endforeach; ?>
        </ul>
