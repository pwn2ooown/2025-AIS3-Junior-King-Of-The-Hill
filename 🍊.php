<?php
    $dir = '/var/www/html/sandbox/' . md5($_SERVER['REMOTE_ADDR']);
    if ( !file_exists($dir) )
        mkdir($dir);
    chdir($dir);
    if (isset($_GET['re_ctf']) && strlen($_GET['re_ctf']) <= 5) {
        @exec($_GET['re_ctf']);
    } else if (isset($_GET['reset'])) {
        @exec('/bin/rm -rf ' . $dir);
    }
    die(highlight_file(__FILE__));
?>