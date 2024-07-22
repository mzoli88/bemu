<?php

namespace mod\naplo\controllers\logevents;

use hws\rmc\Controller3;
use Illuminate\Http\Request;

class ModsRMC extends Controller3
{

    public function list(Request $request)
    {
       return collect(config('mods'))->map(function ($item, $key) {
            return [
                'name' => $item['name'],
                'value' => $key,
            ];
        })->values();
    }

}
