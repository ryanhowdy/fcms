<?php

$dh = opendir('../test/');

while (($file = readdir($dh)) !== false)
{
    // Skip Directories
    if (filetype($file) == "dir")
    {
        continue;
    }

    // Skip this file
    if ($file == "test-all.php")
    {
        continue;
    }

    // Skip index
    if ($file == "index.php")
    {
        continue;
    }

    $results = '';

    $output = exec("php $file", $results, $ret_val);

    $fp = fopen("results.txt","a");

    foreach ($results as $result)
    {
        $arr = explode(':', $result);
        if ($arr[0] == 'X-Powered-By' || $arr[0] == 'Content-type')
        {
           continue;
        }

        fwrite($fp, "$result\n");
    }
}

echo "\nResults: results.txt\n";
