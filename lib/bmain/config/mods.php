<?php
// config()->set('mods') vagy config(['mods' => []]) csak, hogy megtaláljuk, kereső segítségével
return collect(scandir(base_path('mod')))->filter(function ($x) {
    return $x != '.' && $x != '..' && file_exists(base_path('mod/' . $x . '/config.php'));
})
    ->mapWithKeys(function ($x) {
        $data = include(base_path('mod/' . $x . '/config.php'));
        $data['dir'] = base_path('mod/' . $x . '/');
        return [$x => $data];
    })
    ->toArray();
