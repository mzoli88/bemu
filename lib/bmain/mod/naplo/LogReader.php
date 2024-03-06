<?php


namespace mod\naplo;

class LogReader
{

    static $log_folder = "/tmp/";

    public function  __construct($folder)
    {
        self::$log_folder = $folder;
    }


    static function search_row(array $preg_tmb, $buffer)
    {
        foreach ($preg_tmb as $preg) {
            if (preg_match($preg, $buffer)) continue;
            return false;
        }
        return true;
    }

    public function getLog($file_name = 'debug', $search = [], &$total_find = 0, $perPage = 25, $limit = false)
    {
        $matches = [];

        //keresési reguláris kifejezések előállítása
        $search_pregs = false;
        $days = [];
        if (!empty($search)) {

            if (array_key_exists('datetime', $search) && !empty($search['datetime']) && is_string($search['datetime'])) {
                $days[] = $search['datetime'];
            }

            $search_pregs = [];

            foreach ($search as $key => $value) {
                if ($key == 'datetime') continue;
                if(preg_match('/^\[.*\]$/',$value)){
                    $value2 = json_decode($value,true);
                    if($value2)$value = '('.implode('|',$value2).')';
                }
                $value = str_replace('/','\/',$value);
                $search_pregs[] = '/"' . $key . '":"[^"]*' . $value . '[^"]*"/i';
            }
        }
        // dd ($days);
        // dd ($search_pregs);
        if (empty($days)) $days[] = date('Y-m-d');

        foreach ($days as $date) {

            if (empty($search_pregs) && !$limit) {

                //ha nincs keresés akkor ez egy gyorsabb meoldás a total kiszámítására
                // dd (self::$log_folder . $file_name . "-" . $date . '.log');
                if (!is_file(self::$log_folder . $file_name . "-" . $date . '.log')) continue;
                $handle = new \SplFileObject(self::$log_folder . $file_name . "-" . $date . '.log', 'r');
                if (!$handle) continue;

                $handle->seek(PHP_INT_MAX);
                $total_find = $handle->key();

                $to_line = $total_find - $perPage - 1;

                if ($to_line < 1) $to_line = 0;

                $handle->seek($to_line);

                while (!$handle->eof()) {
                    $buffer = $handle->fgets();
                    if (empty($buffer)) continue;
                    $matches[] = ['date' => $date, 'ct' => $buffer];
                }
            } else {
                // print_r(self::$log_folder . $file_name . "-" . $date . '.log');
                if (!is_file(self::$log_folder . $file_name . "-" . $date . '.log')) continue;
                $handle = @fopen(self::$log_folder . $file_name . "-" . $date . '.log', "r");
                if (!$handle) continue;
                while (!feof($handle)) {
                    $buffer = fgets($handle);
                    if (empty($buffer) || ($search_pregs && !self::search_row($search_pregs, $buffer))) continue;
                    $matches[] = ['date' => $date, 'ct' => $buffer];
                    $total_find++;
                    if ($perPage && $total_find > $perPage) array_shift($matches);
                    if ($limit && $limit <= $total_find) break;
                }
                fclose($handle);
            }

            //foreach-ből is ki kell léptetni, ha már meglett a limit
            if ($limit && $limit <= $total_find) break;
        }

        $out = [];
        if (!empty($matches)) {
            $matches = array_reverse($matches);
            foreach ($matches as $key => $row) {
                $tmp = json_decode($row['ct'], true);
                $tmp['message'] = htmlentities($tmp['message'], ENT_QUOTES, "UTF-8");
                $out[] = $tmp;
            }
        }
        return $out;
    }
}
