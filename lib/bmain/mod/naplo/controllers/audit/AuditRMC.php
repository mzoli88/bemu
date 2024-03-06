<?php

namespace mod\naplo\controllers\audit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\naplo\LogReader as NaploLogReader;

class AuditRMC extends Controller3
{

    public function list(Request $request)
    {

        $limit = false;
        $total = 0;

        $limit = false;
        if(isset($_GET['total']) && isset($_GET['page']) && isset($_GET['per-page']) && $_GET['page'] != 1){
			$limit = (int)$_GET['total'] - (((int)$_GET['page'] - 1) * (int)$_GET['per-page']);
		}
        $total = 0;
        $perpage = $request->query('per-page');
        if($_GET['page'] == 1){
            $limit = false;
        }

        $naploClass = new NaploLogReader(storage_path('bmain_logs/'));

        $file_name = $request->fastFilter . '-audit';

        $search = [];

        foreach ($_GET as $key => $value) {
            if ($key == 'log_type') continue;
            if ($key == 'page') continue;
            if ($key == 'getMeta') continue;
            if ($key == 'total') continue;
            if ($key == 'fastFilter') continue;
            if ($key == 'per-page') continue;
            $search[$key] = $value;
        }
        // dd ($file_name, $search, $total, $perpage, $limit) ;

        $data = $naploClass->getLog($file_name, $search, $total, $perpage, $limit);

        return [
            'total' => isset($_GET['total']) && $limit ? $_GET['total'] : $total,
            'data' => $data,
        ];
    }
}
