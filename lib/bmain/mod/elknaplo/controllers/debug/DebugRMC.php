<?php

namespace mod\elknaplo\controllers\debug;

use hws\Chttp;
use hws\rmc\Controller3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use mod\elknaplo\classes\Auth;

class DebugRMC extends Controller3
{

    public function list(Request $request)
    {

        $level = ['error', 'info', 'debug'];
        if ($request->filled('level')) $level = json_decode($request->level, true);

        $shoud = [];

        if (in_array('debug', $level)) $shoud[] = ['term' => ["level" => "debug"]];
        if (in_array('error', $level)) $shoud[] = ['term' => ["level" => "error"]];
        if (in_array('info', $level)) $shoud[] = ['term' => ["level" => "info"]];

        $url = preg_replace(['#\/$#', '#:[0-9]*$#'], '$1$2', getParam('elk_url'));

        $s = Cache::get('elk_sources',[]);
        $fastFilter = $request->fastFilter;
        if(!in_array($request->fastFilter,$s)) $fastFilter = $s[0]??null;
        if (!$fastFilter || $fastFilter == 'all'){
            $chttp = new Chttp($url . ':9200/_search');
        }else{
            $chttp = new Chttp($url . ':9200/' . $fastFilter . '/_search');
        }

        $chttp->CURLOPT_USERPWD = Auth::getUserPasword();

        $must = [['terms' => ['level' => $level]]];

        if ($request->filled('datetime')) $must[] = ['range' => ['datetime' => [
            "gte" => $request->datetime . ' 00:00:00',
            "lte" => $request->datetime . ' 23:59:59',
        ]]];

        if ($request->filled('request_id')) $must[] = ['match' => ['request_id' => $request->request_id]];
        if ($request->filled('uuid')) $must[] = ['match' => ['uuid' => $request->uuid]];
        if ($request->filled('user_id')) $must[] = ['match' => ['user_id' => $request->user_id]];
        if ($request->filled('method')) $must[] = ['match' => ['method' => $request->method]];

        if ($request->filled('uri')) $must[] = ['regexp' => ['uri' => '.*' . preg_quote($request->uri) . '.*']];
        if ($request->filled('entity_name')) $must[] = ['regexp' => ['entity_name' => '.*' . preg_quote($request->entity_name) . '.*']];
        if ($request->filled('user_name')) $must[] = ['regexp' => ['user_name' => '.*' . preg_quote($request->user_name) . '.*']];
        if ($request->filled('ip')) $must[] = ['regexp' => ['ip' => '.*' . preg_quote($request->ip) . '.*']];

        if ($request->filled('message')) $must[] = ['query_string' => ['query' => $request->message, "default_field" => 'message']];

        try {
            $response = $chttp->CREATE([
                "from" => ($_GET['page'] ?? 1) - 1,
                "size" => $_GET['per-page'] ?: 25,
                "sort" =>  ['datetime' => ["order" => "desc",]],
                "query" => [
                    "bool" => [
                        'must' => $must
                    ],
                ]
            ]);
        } catch (\Throwable $th) {
            throw new \Exception($chttp->response, 1);
        }

        $data = array_map(function ($r) {
            return $r['_source'];
        }, $response['hits']['hits'] ?? []);


        return [
            'total' => $response['hits']['total']['value'] ?? 0,
            'data' => $data,
        ];
    }
}
