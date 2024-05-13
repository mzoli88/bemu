<?php

require_once('config.class.php');

$path = realpath(__DIR__ . "/../../mysql");

if(!$path) exit ("bad mqsql path!");
$D = DIRECTORY_SEPARATOR;

function deleteDir(string $dirPath): void {
    if (! is_dir($dirPath)) exit("$dirPath must be a directory");
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/')$dirPath .= '/';
    foreach (glob($dirPath . '*', GLOB_MARK) as $file)
        if (is_dir($file)) deleteDir($file); else unlink($file);
    rmdir($dirPath);
}

function xcopy($src, $dest) {
	if(!is_dir($dest))mkdir($dest, 0777, true);
    foreach (scandir($src) as $file) {
        if (!is_readable($src . DIRECTORY_SEPARATOR . $file)) continue;
        if ($file == '.' || $file == '..') continue;
        if (is_dir($src .DIRECTORY_SEPARATOR . $file)) {
            mkdir($dest . DIRECTORY_SEPARATOR . $file, 0777, true);
            xcopy($src . DIRECTORY_SEPARATOR . $file, $dest . DIRECTORY_SEPARATOR . $file);
        } else {
            copy($src . DIRECTORY_SEPARATOR . $file, $dest . DIRECTORY_SEPARATOR . $file);
        }
    }
}

$bpath = $path.$D."b_emu_backup";

if(is_dir($bpath))deleteDir($path.$D."b_emu_backup");
mkdir($bpath, 0777, true);
mkdir($bpath.$D.BORDER_DB_DATABASE, 0777, true);

xcopy($path.$D."data".$D.BORDER_DB_DATABASE,$bpath.$D.BORDER_DB_DATABASE);
copy($path.$D."data".$D.'ibdata1', $bpath.$D.'ibdata1');

rename($path.$D."data",$path.$D."data_".date('YmdHis'));
mkdir($path.$D."data", 0777, true);
xcopy($path.$D."backup",$path.$D."data");

copy($bpath.$D.'ibdata1',$path.$D."data".$D.'ibdata1');
xcopy($bpath.$D.BORDER_DB_DATABASE,$path.$D."data".$D.BORDER_DB_DATABASE);

if(is_dir($bpath))deleteDir($path.$D."b_emu_backup");
