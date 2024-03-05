<?php

if (!function_exists('getDirContentsMZ')) {
    function getDirContentsMZ($dir)
    {
        $files = scandir($dir);
        $results = array();
        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $results[] = $value;
            } else if ($value != "." && $value != "..") {
                foreach (getDirContentsMZ($path) as $value2) $results[] = $value . DIRECTORY_SEPARATOR . $value2;
            }
        }
        return $results;
    }
}

return collect(scandir(base_path('mod')))->filter(function ($x) {
    return $x != '.' && $x != '..' && file_exists(base_path('mod/' . $x . '/config.php'));
})->map(function ($k) {
    if (!is_dir(base_path('mod/' . $k . '/models'))) return [];
    return collect(getDirContentsMZ(base_path('mod/' . $k . '/models')))
        ->map(function ($path) use ($k) {
            $class = '\\mod\\' . $k . '\\models\\' . strtr(substr($path, 0, strrpos($path, '.')), '/', '\\');
            return method_exists($class, 'getHiddenFields') ? $class::getHiddenFields() : [];
        })->filter(function ($v) {
            return !empty($v);
        })->flatten()->toArray();
})->filter(function ($v) {
    return !empty($v);
})->flatten()->unique()->toArray();
