<?php

namespace mod\elknaplo\controllers\debug;

use hws\Chttp;
use hws\rmc\Controller3;
use Illuminate\Support\Facades\Cache;
use mod\elknaplo\classes\Auth;

class ModsRMC extends Controller3
{

    public function list()
    {
        $url = preg_replace(['#\/$#', '#:[0-9]*$#'], '$1$2', getParam('elk_url'));
        $chttp = new Chttp($url . ':9200/_security/user/_privileges');
        $chttp->CURLOPT_USERPWD = Auth::getUserPasword();

        try {
            $response = $chttp->get();
        } catch (\Throwable $th) {
            sendError($chttp->response, $chttp->status ?: 401);
        }

        $data = [];
        $data['all'] = 'Összes';

        foreach ($response['indices'] ?? [] as $r) {
            foreach ($r['names'] as $n) {
                $data[$n] = $n;
            }
        }

        Cache::set('elk_sources', $data);
        return $data;
    }
}
