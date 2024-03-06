<?php

namespace mod\naplo\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DocumentDatabase extends Command
{

    protected $signature = 'doc:db {prefix?*}';


    protected $description = 'Generate document for database';


    public function handle()
    {
        // $modul_azon = $this->argument('modul_azon');
        $prefix = $this->argument('prefix');
        if (empty($prefix)) return $this->error('no prefix in param');

        ob_start();


        echo '<!DOCTYPE html>
<html>
<head>
<title>Page Title</title>
<style>
*{
	font-family: Calibri,Candara,Segoe,Segoe UI,Optima,Arial,sans-serif;
	font-size: 16px;
}
h1,h2{
	font-size: 20px;
	color:#1e5999;
	font-weight: normal;
}
table{
	border-collapse: collapse;
}
td,th{
	padding: 0px 10px;
	border: 1px solid #999;
	cellspacing:0;
	text-align: left;
	vertical-align: middle;
}
th{
	background: #ddf;
	font-weight: bold;
}
td:first-child{
	font-weight: bold;
}
tr:nth-child(odd) {
    background: #F5F5F5;
}
input{width:100%;}
</style>
</head>
<body>';

        $all = DB::table('information_schema.TABLES')
            ->select('TABLE_NAME', 'TABLE_COMMENT')
            ->where(function ($q) use ($prefix) {
                collect($prefix)->map(function ($r) use ($q) {
                    $q->orWhere('TABLE_NAME', 'LIKE', $r . '%');
                });
            })
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->get()
            ->toArray();
        // dd($all);
        echo "<h1>1. Összesítő</h1>";
        $this->print_table($all, ['Tábla', 'Megjegyzés'],);
        echo "<h2>2. Táblák</h2>";

        $ct = 0;
        //Táblánkénti adatok
        foreach ($all as $table) {
            $ct++;
            $tname = $table->TABLE_NAME;

            $cols = DB::table('information_schema.COLUMNS')
                ->where('TABLE_NAME', $tname)
                ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
                ->get()
                ->toArray();

            $cols = json_decode(json_encode($cols), true);
            $cols = collect($cols)->sortBy('ORDINAL_POSITION')->toArray();

            $out = array_map(function ($row) {
                return [
                    $row['COLUMN_NAME'],
                    $row['COLUMN_TYPE'] .
                        ((!empty($row['EXTRA'])) ? ' ' . $row['EXTRA'] : '') .
                        (($row['IS_NULLABLE'] == 'YES') ? ' NULL' : '') .
                        ((!empty($row['COLUMN_DEFAULT']) && $row['COLUMN_DEFAULT'] != "NULL") ? ' [' . str_replace('\'', '', $row['COLUMN_DEFAULT']) . ']' : ''),
                    $row['COLUMN_COMMENT'],
                ];
            }, $cols);


            echo "<h2>2.$ct. $tname</h2>";
            $this->print_table($out, ['Oszlopok', 'Típus', 'Megjegyzés'],);

            //Index -ek lekérdezése
            $index = DB::select('SHOW INDEX FROM ' . $tname);
            $index = json_decode(json_encode($index), true);

            if (!empty($index)) {
                // echo '<br>';
                echo "<h3>Indexek</h3>";
                $out = array_map(function ($row) {
                    return [
                        $row['type'],
                        implode(', ', $row['columns']),
                    ];
                }, $this->indexes($index));
                $this->print_table($out);
            }


            $out = array_map(function ($r) {
                return [
                    implode(", ", $r["source"]),
                    $r['table'] . "(" . implode(", ", $r["target"]) . ")",
                    $r["on_delete"],
                    $r["on_update"],
                ];
            }, $this->foreign_keys($tname));

            if (!empty($out)) {
                // echo '<br>';
                echo "<h3>Idegen kulcsok</h3>";
                $this->print_table($out, ['Forrás', 'Cél', 'Törlés', 'Módosítás']);
            }
        }

        echo '</body>
        </html>';

        // dd(ob_get_contents());
        file_put_contents(__DIR__ .'/generated.html',ob_get_contents());
    }



    public function print_table($arrray, $cols = null)
    {
        echo '<table>';
        if ($cols) {

            echo '<thead>';
            echo '<tr>';
            foreach ($cols as $key => $val) {
                echo '<th>';
                echo $val;
                echo '</th>';
            }
            echo '</tr>';
        }

        echo '</thead>';
        echo '<tbody>';
        foreach ($arrray as $key => $val) {
            echo '<tr>';
            foreach ($val as $key2 => $val2) {
                echo '<td>';
                echo $val2;
                echo '</td>';
            }

            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }

    public function indexes($index)
    {
        $I = array();
        foreach ($index as $J) {
            $C = $J["Key_name"];
            $I[$C]["type"] = ($C == "PRIMARY" ? "PRIMARY" : ($J["Index_type"] == "FULLTEXT" ? "FULLTEXT" : ($J["Non_unique"] ? ($J["Index_type"] == "SPATIAL" ? "SPATIAL" : "INDEX") : "UNIQUE")));
            $I[$C]["columns"][] = $J["Column_name"] . ($J["Index_type"] == "SPATIAL" ? null : (empty($J["Sub_part"]) ? '' : '(' . $J["Sub_part"] . ')'));
        }
        return $I;
    }

    public function idf_unescape($v)
    {
        $le = substr($v, -1);
        return str_replace($le . $le, $le, substr($v, 1, -1));
    }

    public function foreign_keys($table)
    {
        // global $g,$nf;
        $nf = "RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT";
        static $Xf = '(?:`(?:[^`]|``)+`)|(?:"(?:[^"]|"")+")';
        $I = array();

        $Db = DB::select('SHOW CREATE TABLE ' . $table);
        $Db = json_decode(json_encode($Db), true);

        $Db = $Db['0']['Create Table'];
        // dd($Db);

        if ($Db) {
            preg_match_all("~CONSTRAINT ($Xf) FOREIGN KEY ?\\(((?:$Xf,? ?)+)\\) REFERENCES ($Xf)(?:\\.($Xf))? \\(((?:$Xf,? ?)+)\\)(?: ON DELETE ($nf))?(?: ON UPDATE ($nf))?~", $Db,    $Be, PREG_SET_ORDER);
            foreach ($Be as $B) {
                preg_match_all("~$Xf~", $B[2], $th);
                preg_match_all("~$Xf~", $B[5], $Vh);
                $I[$this->idf_unescape($B[1])] = [
                    // "db"=>idf_unescape($B[4]!=""?$B[3]:$B[4]),
                    "table" => $this->idf_unescape($B[4] != "" ? $B[4] : $B[3]),
                    "source" => array_map([$this, 'idf_unescape'], $th[0]),
                    "target" => array_map([$this, 'idf_unescape'], $Vh[0]),
                    "on_delete" => (array_key_exists(6, $B) && $B[6] ? $B[6] : "RESTRICT"),
                    "on_update" => (array_key_exists(7, $B) && $B[7] ? $B[7] : "RESTRICT"),

                ];
            }
        }
        return $I;
    }
}
