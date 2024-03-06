<?php

namespace mod\naplo\commands;

use Illuminate\Console\Command;

class DocumentFiles extends Command
{

    protected $signature = 'doc:files {dirs?*}';


    protected $description = 'Generate document for files';


    public function handle()
    {
        $dirs = $this->argument('dirs');

        // dd ($dirs);

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


        collect($dirs)->each(function ($dir) {
            // $dir = str_replace( ['/','\\'],DIRECTORY_SEPARATOR ,$_GET['dir']);
            $dir = base_path($dir);
            // dd(1, $dir);

            if (!is_dir($dir)) $this->error('bad dir path' . $dir);

            $out = array_map(function ($d) {
                return [$d, 'valami'];
            }, $this->scanAllDir($dir));

            $this->print_table($out, ['Fájl', 'Magyarázat']);
            echo '<br>';
        });

        echo '</body>
        </html>';


        file_put_contents(__DIR__ . '/generated.html', ob_get_contents());
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

    public function scanAllDir($dir)
    {
        $result = [];
        foreach (scandir($dir) as $filename) {
            if ($filename[0] === '.') continue;
            
            $filePath = $dir . '/' . $filename;
            if (is_dir($filePath)) {
                if ($filename == 'migrations') continue;
                foreach ($this->scanAllDir($filePath) as $childFilename) {
                    $result[] = $filename . '/' . $childFilename;
                }
            } else {
                $result[] = $filename;
            }
        }
        return $result;
    }
}
