    <div id="toolbar" class="toolbar hideme">
        <input type="button" class="bold button" 
            onclick="bb.insertCode('B', 'bold');" onmouseout="style.border='1px solid #f6f6f6';" onmouseover="style.border='1px solid #c1c1c1';" 
            title="<?php echo $TMPL['boldText']; ?>" />
        <input type="button" class="italic button" 
            onclick="bb.insertCode('I', 'italic');" onmouseout="style.border='1px solid #f6f6f6';" onmouseover="style.border='1px solid #c1c1c1';" 
            title="<?php echo $TMPL['italicText']; ?>"/>
        <input type="button" class="underline button" 
            onclick="bb.insertCode('U', 'underline');" onmouseout="style.border='1px solid #f6f6f6';" onmouseover="style.border='1px solid #c1c1c1';" 
            title="<?php echo $TMPL['underlineText']; ?>"/>
        <input type="button" class="left_align button" 
            onclick="bb.insertCode('ALIGN=LEFT', 'left right', 'ALIGN');" onmouseout="style.border='1px solid #f6f6f6';" onmouseover="style.border='1px solid #c1c1c1';" 
            title="<?php echo $TMPL['leftAlignText']; ?>"/>
        <input type="button" class="center_align button" 
            onclick="bb.insertCode('ALIGN=CENTER', 'center', 'ALIGN');" onmouseout="style.border='1px solid #f6f6f6';" onmouseover="style.border='1px solid #c1c1c1';" 
            title="<?php echo $TMPL['centerText']; ?>"/>
        <input type="button" class="right_align button" 
            onclick="bb.insertCode('ALIGN=RIGHT', 'align right', 'ALIGN');" onmouseout="style.border='1px solid #f6f6f6';" onmouseover="style.border='1px solid #c1c1c1';" 
            title="<?php echo $TMPL['rightAlignText']; ?>"/>
        <input type="button" class="h1 button" 
            onclick="bb.insertCode('H1', 'heading 1');" onmouseout="style.border='1px solid #f6f6f6';" onmouseover="style.border='1px solid #c1c1c1';" 
            title="<?php echo $TMPL['heading1Text']; ?>"/>
        <input type="button" class="h2 button" 
            onclick="bb.insertCode('H2', 'heading 2');" onmouseout="style.border='1px solid #f6f6f6';" onmouseover="style.border='1px solid #c1c1c1';" 
            title="<?php echo $TMPL['heading2Text']; ?>"/>
        <input type="button" class="h3 button" 
            onclick="bb.insertCode('H3', 'heading 3');" onmouseout="style.border='1px solid #f6f6f6';" onmouseover="style.border='1px solid #c1c1c1';" 
            title="<?php echo $TMPL['heading3Text']; ?>"/>
        <input type="button" class="board_quote button" 
            onclick="bb.insertCode('QUOTE', 'quote');" onmouseout="style.border='1px solid #f6f6f6';" onmouseover="style.border='1px solid #c1c1c1';" 
            title="<?php echo $TMPL['quoteText']; ?>"/>
        <input type="button" class="board_images button" 
            onclick="window.open('inc/upimages.php','name','width=700,height=500,scrollbars=yes,resizable=no,location=no,menubar=no,status=no'); return false;" 
            onmouseout="style.border='1px solid #f6f6f6';" onmouseover="style.border='1px solid #c1c1c1';" 
            title="<?php echo $TMPL['insertImageText']; ?>"/>
        <input type="button" class="links button" 
            onclick="bb.insertLink();" onmouseout="style.border='1px solid #f6f6f6';" onmouseover="style.border='1px solid #c1c1c1';" 
            title="<?php echo $TMPL['insertUrlText']; ?>"/>
        <input type="button" class="smileys button" 
            onclick="window.open('inc/smileys.php','name','width=500,height=200,scrollbars=no,resizable=no,location=no,menubar=no,status=no'); return false;" 
            onmouseout="style.border='1px solid #f6f6f6';" onmouseover="style.border='1px solid #c1c1c1';" 
            title="<?php echo $TMPL['insertSmileyText']; ?>"/>
        <input type="button" class="help button" 
            onclick="window.open('inc/bbcode.php','name','width=400,height=300,scrollbars=yes,resizable=no,location=no,menubar=no,status=no'); return false;" 
            onmouseout="style.border='1px solid #f6f6f6';" onmouseover="style.border='1px solid #c1c1c1';" 
            title="<?php echo $TMPL['bbcodeHelpText']; ?>"/>
    </div>
