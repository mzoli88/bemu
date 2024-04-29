<?php

$output = shell_exec('composer install');
$output = preg_replace('#\\x1b[[][^A-Za-z]*[A-Za-z]#', '', $output);

echo "<pre>$output</pre>";
$output = shell_exec('php artisan migrate --force');
echo "<pre>$output</pre>";