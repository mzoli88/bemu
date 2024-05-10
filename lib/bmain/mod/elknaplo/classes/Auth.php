<?php

namespace mod\elknaplo\classes;

use hws\Chttp;
use hws\rmc\Controller3;

class Auth extends Controller3
{

    static function getUserPasword()
    {
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate: Basic realm="My Realm"');
            header('HTTP/1.0 401 Unauthorized');
            exit('[]');
        }
        return $_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW'];
    }
}
