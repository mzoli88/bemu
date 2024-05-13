<?php

namespace mod\elknaplo\controllers\params;

use hws\rmc\Controller3;
use Illuminate\Http\Request;

class ParamsRMC extends Controller3
{
    public function list(Request $request)
    {
        return getParams();
    }

    public function create(request $request)
    {
        return setParams($request->all());
    }
}
