<?php

//Csak vscode plugin miatt h ne húzza alá hibának a definiált változókat
//Sose innen vesszük a paramétereket, csak egy minta fájl, hogy milyen border config-ok szükségesek


define('BORDER_DB_HOST', 'localhost');
define('BORDER_DB_USER', 'root');
define('BORDER_DB_PASSWORD', '');
define('BORDER_DB_DATABASE', 'border');

define('USE_ENTITY_WORKFLOW', true);

define('BORDER_PATH_BORDER', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..')  . DIRECTORY_SEPARATOR);
define('BORDER_PATH_BORDERLIB', BORDER_PATH_BORDER . 'lib' . DIRECTORY_SEPARATOR);
define('BORDER_PATH_BORDERDOC', BORDER_PATH_BORDER . 'doc' . DIRECTORY_SEPARATOR);
define('BORDER_PREFIX', 'bemu_');
define('BORDER_EMU', true);
define('BORDER_PATH_JAVA', 'java');
