
    </div>
    <!-- ############ CONTENT END ############ -->

<?php $ver = getCurrentVersion(); $date = date('Y'); ?>

    <div id="footer">
        <p>
            <a href="<?php echo $TMPL['path']; ?>index.php" class="ft"><?php echo _('Home') ?></a> | 
            <a href="http://www.familycms.com/forum/index.php" class="ft"><?php echo _('Support Forum') ?></a> | 
            <a href="<?php echo $TMPL['path']; ?>help.php" class="ft"><?php echo _('Help'); ?></a><br />
            <a href="http://www.familycms.com"><?php echo $ver; ?></a> - Copyright &copy; 2006-<?php echo $date; ?> Ryan Haudenschilt.
        </p>
    </div>

</body>
</html>
