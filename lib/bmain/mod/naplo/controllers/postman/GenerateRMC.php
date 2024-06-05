<?php

namespace mod\naplo\controllers\postman;

use App\Logging\CustomLoggerHandler;
use hws\rmc\Controller3;
use hws\Sablon;
use Illuminate\Http\Request;

class GenerateRMC extends Controller3
{

    public $output = '<style>
    *{
        font-family: Calibri,Candara,Segoe,Segoe UI,Optima,Arial,sans-serif;
        font-size: 16px;
    }
    h1{
        font-size: 20px;
        color:#1e5999;
    }
    h2{
        font-size: 20px;
        color:#1e5999;
        font-weight: normal;
        margin: 0;
    }
    table{
        border-collapse: collapse;
    }
    td,th{
        padding: 10px;
        border: 1px solid #999;
        cellspacing:0;
        text-align: left;
        vertical-align: middle;
    }
    </style>';

    public function create(Request $request)
    {
        $items = json_decode(base64_decode($request->file['file_content']), true)['item'] ?? [];
        collect($items)->each(function ($item) {
            
            $item['response'] = self::format_json ($item['response']['0']['body'] ?? '');
            $item['request_body'] = self::format_json ($item['request']['body']['raw'] ?? '');

            $this->addItem($item);
        });

        return response()->streamDownload(function () {
            echo $this->output;
        }, basename('postman_' . date('YmdHis')) . '.html');
    }

    public function addItem($item)
    {
        $this->output .= Sablon::create(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'sablon.html'), $item);
    }

    static function format_json($json_string)
    {
        try {
            //file kivágás
            $tmp = json_decode($json_string);
            $json_string = json_encode($tmp);
            $json_string = preg_replace('/"file_content":"([^"]*?)"/', '"file_content":"file_content"', $json_string);
            // dump ($json_string);
            //response formázás
            $tmp = json_decode($json_string);
            $json_string = json_encode($tmp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            // dump ($json_string);
            return $json_string;
        } catch (\Throwable $th) {
            return '';
        }
    }
}
