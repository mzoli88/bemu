<?php
return [];
// collect(scandir(base_path('mod')))->filter(function ($x) {
//     return $x != '.' && $x != '..' && file_exists(base_path('mod/' . $x . '/messageTypes.php'));
// })
//     ->mapWithKeys(function ($x) {
//         $data = include(base_path('mod/' . $x . '/messageTypes.php'));
//         return [$x => $data];
//     })
//     ->toArray();