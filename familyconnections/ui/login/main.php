
    <div id="login_box">

    <?php if (isset($TMPL['sitename'])): ?>
        <a href="index.php" id="login_header">
            <?php echo T_('Login to'); echo ' '.$TMPL['sitename']; ?>
        </a>
    <?php endif; ?>

    <?php if (isset($TMPL['message'])): ?>
        <div class="<?php echo $TMPL['message']['type']; ?>">
            <h2><?php echo $TMPL['message']['title']; ?></h2>
        <?php foreach ($TMPL['message']['messages'] as $m): ?>
            <p><?php echo $m; ?></p>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php if (!isset($TMPL['noForm'])): ?>
        <form action="index.php" method="post">
            <div style="float:right">
                <select style="background-color:#e9f3fb; border:none;" onchange="window.location.href='?lang='+this.options[this.selectedIndex].value;">
                    <option><?php echo T_('Language'); ?>:</option>
                <?php foreach ($TMPL['languageOptions'] as $o): ?>
                    <option value="<?php echo $o['value']; ?>" <?php echo $o['selected']; ?>>
                        <?php echo $o['language']; ?>
                    </option>
                <?php endforeach; ?>
                </select>
            </div>
            <p>
                <label for="user"><?php echo $TMPL['usernameText']; ?>:</label>
                <input type="text" name="user" id="user" autocorrect="off" autocapitalize="none"/>
            </p>
            <p>
                <label for="pass"><?php echo $TMPL['passwordText']; ?>:</label>
                <input type="password" name="pass" id="pass" autocorrect="off" autocapitalize="none"/>
            </p>
            <p>
                <label class="rem" for="rem"><?php echo $TMPL['rememberMeText']; ?></label>
                <input class="rem" name="rem" id="rem" type="checkbox" value="1"/>
            <?php if (isset($TMPL['redirectUrl'])): ?>
                <?php echo $TMPL['redirectUrl']; ?>
            <?php endif; ?>
                <input type="submit" name="submit" id="submit" value="<?php echo $TMPL['loginText']; ?>"/>
            </p>
            <div class="clear"></div>
        </form>
        <p style="text-align:center; margin-bottom:20px;">
            <a href="lostpw.php"><?php echo $TMPL['forgotPasswordText']; ?></a>
            <?php if (isset($TMPL['registerText'])): ?>| <a href="register.php"><?php echo $TMPL['registerText']; ?></a><?php endif; ?>
        </p>
        <div style="color:silver; font-size:11px; float:left;"><?php echo $TMPL['currentVersion']; ?></div>
    <?php if (isset($TMPL['facebookLogin'])): ?>
        <div style="float:right">
            <a href="<?php echo $TMPL['facebookLogin']['url']; ?>" title="<?php echo $TMPL['facebookLogin']['text']; ?>"><img src="ui/img/facebook_tiny.png"/></a>
        </div>
    <?php endif; ?>

<?php endif; ?>

    </div>
