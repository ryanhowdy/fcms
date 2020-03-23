
            <div id="leftcolumn">
                <?php require_once TEMPLATES.'addressbook/menu.php'; ?>
            </div>

            <div id="maincolumn">

                <div id="address-options">
                    <ul>
                        <?php foreach ($TMPL['addressOptions'] as $o) { ?>
                        <li id="<?php echo $o['liId']; ?>">
                            <a id="<?php echo $o['aId']; ?>" href="<?php echo $o['url']; ?>"><?php echo $o['text']; ?></a>
                        </li>
                        <?php } ?>
                    </ul>
                </div>

                <div id="address-details">
                    <p>
                        <img alt="avatar" src="<?php echo $TMPL['avatar']; ?>"/>
                        <b class="name"><?php echo $TMPL['name']; ?></b>
                    </p>
                    <p>
                        <b class="label"><?php echo $TMPL['addressText']; ?>:</b>
                        <span class="data">
                            <?php echo $TMPL['address']; ?>
                            <?php if (isset($TMPL['mapText'])) { ?>
                            <a href="http://maps.google.com/maps?q=<?php echo $TMPL['addressUrl']; ?>"><?php echo $TMPL['mapText']; ?></a>
                            <?php } ?>
                        </span>
                    </p>
                    <p>
                        <b class="label"><?php echo $TMPL['emailText']; ?>:</b>
                        <span class="data">
                            <?php echo $TMPL['email']; ?>
                            <a class="email" href="mailto:<?php echo $TMPL['email']; ?>"
                                title="<?php echo $TMPL['emailMemberText']; ?>">&nbsp;</a>
                        </span>
                    </p>
                    <p>
                        <b class="label"><?php echo $TMPL['homeText']; ?>:</b>
                        <span class="data"><?php echo $TMPL['home']; ?></span>
                    </p>
                    <p>
                        <b class="label"><?php echo $TMPL['workText']; ?>:</b>
                        <span class="data"><?php echo $TMPL['work']; ?></span>
                    </p>
                    <p>
                        <b class="label"><?php echo $TMPL['mobileText']; ?>:</b>
                        <span class="data"><?php echo $TMPL['mobile']; ?></span>
                    </p>
                </div>

            </div>

