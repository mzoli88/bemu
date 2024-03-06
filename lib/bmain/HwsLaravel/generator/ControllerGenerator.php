<?php

namespace hws\generator;

use hws\generator\cls\ControllerCls;

class ControllerGenerator
{
    static $name_space = null;
    static $file_path = null;
    static $modul_azon = null;

    public function __construct(String $name, $methods = null)
    {
        (new ControllerCls($name, $methods, self::$name_space, self::$file_path, self::$modul_azon))->process();
    }

}