<?php

namespace hws;

class UploadCheck
{

    static $allowedTypes = [
        'application/json' => ['json'],
        'application/msword' =>    ['doc', 'dot'],
        'application/pdf' => ['pdf'],
        'application/xml' => ['xml', 'xsd'],
        'application/zip' => ['zip'],
        'image/gif' => ['gif'],
        'image/jpeg' => ['jpeg', 'jpg', 'jpe'],
        'image/png' => ['png'],
        'image/svg+xml' => ['svg', 'svgz'],
        'image/vnd.microsoft.icon' => ['ico'],
        'image/x-ms-bmp' => ['bmp'],
        'text/csv' => ['csv'],
        'text/richtext' => ['rtx'],
        'text/plain' => ['txt', 'text', 'csv'],
        'application/vnd.ms-excel' => ['xls', 'xlb', 'xlt'],
        'application/vnd.ms-powerpoint' => ['ppt', 'pps'],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['pptx'],
        'application/vnd.openxmlformats-officedocument.presentationml.slide' => ['sldx'],
        'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => ['ppsx'],
        'application/vnd.openxmlformats-officedocument.presentationml.template' => ['potx'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.template' => ['xltx'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.template' => ['dotx'],
        'application/vnd.visio' => ['vsd', 'vst', 'vsw', 'vss'],
        'audio/midi' => ['mid', 'midi', 'kar'],
        'audio/mpeg' => ['mpga', 'mpega', 'mp2', 'mp3', 'm4a'],
        'audio/x-ms-wma' => ['wma'],
        'audio/x-wav' => ['wav'],
        'video/x-ms-wmv' => ['wmv'],
        'video/x-flv' => ['flv'],
        'video/mpeg' => ['mpg','mpeg'],
        'video/x-msvideo' => ['avi'],
        'video/x-ms-asf' => ['asf', 'wmv'],
        'video/mp4' => ['mp4'],
        'video/x-flv' => ['flv'],
        'video/ogg' => ['ogv'],
        'video/mp2t' => ['ts'],
        'video/webm' => ['webm'],
    ];

    static function run($filepath, $file_name, $allowedTypes = false, $delete = false)
    {

        $allowedTypes = $allowedTypes ?: self::$allowedTypes;

        $size = filesize($filepath);
        if ($size === 0) {
            if ($delete) unlink($filepath);
            return "Fájl tartalma üres! " . $file_name;
        }

        $filetype = mime_content_type($filepath);

        if (!in_array($filetype, array_keys($allowedTypes))) {
            if ($delete) unlink($filepath);
            return "Fájl nem megfelelő formátumú! " . $file_name;
        }

        $tmp = explode('.', $file_name);
        $tocheckext = strtolower(end($tmp));
        unset($tmp);

        if (!in_array($tocheckext, $allowedTypes[$filetype])) {
            if ($delete) unlink($filepath);
            return "Fájl nem megfelelő formátumú! " . $file_name;
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' && null !== shell_exec("command -v clamdscan")) {
            $safe_path = escapeshellarg($filepath);
            $command = 'clamdscan --no-summary --fdpass ' . $safe_path;

            $out = '';
            $int = -1;
            exec($command, $out, $int);
            if ($int != 0) {
                if ($delete) unlink($filepath);
                return "A fájl vírust tartalmaz! " . $file_name;
            }
        }

        return false;
    }

    static function runFromJson($file, $allowedTypes = false)
    {
        $temp_name = tempnam("/tmp", 'rmcupload_');
        file_put_contents($temp_name, base64_decode($file["file_content"]));
        $has_err = UploadCheck::run($temp_name, $file['name'], $allowedTypes);
        unlink($temp_name);
        if ($has_err) sendError($has_err);
    }
}
