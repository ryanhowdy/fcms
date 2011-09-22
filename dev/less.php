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

$theme = isset($argv[1]) ? $argv[1] : 'default';

system("clear");

if (file_exists("$dir/themes/$theme/style.css"))
{
    echo "\n[ themes/$theme/style.css ] already exists.\n\n";
    echo "Overwrite [ y/n ] ? ";
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    if (trim($line) != 'y')
    {
        exit;
    }
}

$worked = system("php -q ~/bin/lessphp/lessc $dir/themes/$theme/dev.less > $dir/themes/$theme/style.css");

if ($worked !== false)
{
    echo "\nFile [ $dir/themes/$theme/style.css ] created successfully.\n\n\n";
}
exit();
