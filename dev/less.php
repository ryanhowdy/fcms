<?php
/**
 * A command line tool for converting dev.less into style.css.
 *  
 * Options:
 *  
 *   theme - the name of the theme, defaults to 'default'
 *  
 *  
 * Requirements:
 *  
 *   lessphp [ https://github.com/dresende/less.php ] installed at ~/bin/lessphp/lessc
 *  
 */

$devdir = dirname(__FILE__);

$dir = substr($devdir, 0, -4);

$theme = 'default';

if (isset($argv[1]))
{
    $theme = basename($argv[1]);
}

system("clear");

if (file_exists("$dir/ui/themes/$theme/style.css"))
{
    echo "\n[ ui/themes/$theme/style.css ] already exists.\n\n";
    echo "Overwrite [ y/n ] ? ";
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    if (trim($line) != 'y')
    {
        exit;
    }
}

$worked = system("php -q ~/bin/lessphp/lessc $dir/ui/themes/$theme/dev.less > $dir/ui/themes/$theme/style.css");

if ($worked !== false)
{
    echo "\nFile [ $dir/ui/themes/$theme/style.css ] created successfully.\n\n\n";
}
exit();
