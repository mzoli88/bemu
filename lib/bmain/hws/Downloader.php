<?php

namespace hws;

use Exception;
use Illuminate\Support\Facades\Storage;

class Downloader
{

    static function storage($path, $file_name, $delete = false)
    {
        set_time_limit(0);
        if (ob_get_level()) ob_end_clean();
        $fs = Storage::getDriver();

        if (!Storage::exists($path)) sendError("Letöltendő fájl nem található!");

        $handle = $fs->readStream($path);
        self::run($handle, Storage::size($path), Storage::mimeType($path), $file_name);
        if ($delete) Storage::delete($path);
        exit;
    }


    static function local($path, $file_name, $delete = false)
    {
        if (!file_exists($path)) sendError("Letöltendő fájl nem található!");

        $handle = fopen($path, 'r');
        if (!$handle) sendError("Letöltendő fájl nem olvasható!");
        self::run($handle, filesize($path), filetype($path), $file_name);
        if ($delete) unlink($path);
        exit;
    }

    static function run($handle, $size, $type, $file_name)
    {
        $file_name = preg_replace('/\s+/', '_', trim($file_name));
        $file_name = str_replace("\n", '', $file_name);

        header('Content-Disposition: attachment; filename="' . urlencode($file_name) . '";');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Length: ' . $size);
        header('Content-Type: ' . $type);
        header('Content-Description: File Transfer');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: private', false);
        header('Pragma: public');
        header('Expires: 0');

        $chunkSize = 1024 * 1024;

        while (!feof($handle) && (connection_status() == 0)) {
            echo fread($handle, $chunkSize);
            flush();
        }

        fclose($handle);
        hwslog(null, 'Fájl letöltés', $file_name . ' fájl letöltve.');
    }
}
