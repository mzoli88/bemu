<?php


namespace hws\generator\cls;

class ControllerCls
{

    static $controller_ending = 'RMC'; //'Controller';
    static $base_file_path = 'app/api';
    static $base_namespace = 'App\api';
    static $modul_azon = null;

    static $method_map = [
        'l' => 'list',
        'list' => 'list',
        'v' => 'view',
        'v' => 'view',
        'view' => 'view',
        'c' => 'create',
        'create' => 'create',
        'u' => 'update',
        'update' => 'update',
        'd' => 'delete',
        'delete' => 'delete',
        'e' => 'export',
        'export' => 'export',
        'csv' => 'csv',
        'pdf' => 'pdf',
        'xls' => 'xls',
        'im' => 'import',
        'import' => 'import',
    ];

    protected $ct_name;
    protected $ct_namespace;
    protected $ct_namespace_full;
    protected $selected_methods = [];

    public function __construct(String $name, $methods = [], $name_space, $file_path, $modul_azon)
    {
        $name = str_replace('\\', '/', $name);
        $tmp = explode('/', $name);

        if ($name_space) {
            self::$base_namespace = $name_space;
        }
        if ($file_path) {
            self::$base_file_path = $file_path;
        }
        if ($modul_azon) {
            self::$modul_azon = $modul_azon;
        }

        $this->ct_name = ucfirst(array_pop($tmp));

        if (!preg_match('/' . self::$controller_ending . '$/', $this->ct_name)) $this->ct_name .= self::$controller_ending;

        $this->ct_namespace = $this->ct_namespace_full = implode('\\', $tmp);

        if (self::$base_namespace != $this->ct_namespace_full) $this->ct_namespace_full = self::$base_namespace . (empty($tmp) ? '' : '' . $this->ct_namespace_full);

        if (!empty($methods)) {
            foreach ($methods as $k) {
                if (array_key_exists($k, self::$method_map)) $this->selected_methods[] = self::$method_map[$k];
            }
        }
    }

    public function process()
    {
        $this->make();
        return $this;
    }

    public function fkdir($path)
    {
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        $tmp_exp = explode(DIRECTORY_SEPARATOR, $path);
        array_pop($tmp_exp);
        $tmp_string = array_shift($tmp_exp);
        if (!empty($tmp_exp)) {
            foreach ($tmp_exp as $next) {
                $tmp_string .= DIRECTORY_SEPARATOR . $next;
                if (!is_dir($tmp_string)) mkdir($tmp_string);
            }
        }
        return $path;
    }

    public function make()
    {
        $template = realpath(__DIR__ . "/../temp/controller.blade.php");
        if ($template) {
            $file_content = view()->file($template)->with([
                "php_start" => "?php",
                "ct_namespace" => $this->ct_namespace_full,
                "ct_name" => $this->ct_name,
                'list' => in_array('list', $this->selected_methods),
                'view' => in_array('view', $this->selected_methods),
                'create' => in_array('create', $this->selected_methods),
                'update' => in_array('update', $this->selected_methods),
                'delete' => in_array('delete', $this->selected_methods),
                'export' => in_array('export', $this->selected_methods),
                'csv' => in_array('csv', $this->selected_methods),
                'pdf' => in_array('pdf', $this->selected_methods),
                'xls' => in_array('xls', $this->selected_methods),
                'import' => in_array('import', $this->selected_methods),
            ])->render();
            $dir = self::$base_file_path . (empty($this->ct_namespace) ? '' : '/' . $this->ct_namespace);
            if (self::$modul_azon) {
                file_put_contents($this->fkdir($dir . "/" . $this->ct_name . '.php'), $file_content);
            } else {
                file_put_contents($this->fkdir(app()->basePath() . "/" . $dir . "/" . $this->ct_name . '.php'), $file_content);
            }
        }
    }
}
