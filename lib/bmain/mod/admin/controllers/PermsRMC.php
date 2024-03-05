<?php

namespace mod\admin\controllers;

use App\Border3;
use hws\rmc\Controller3;

class PermsRMC extends Controller3
{

    public function list()
    {
        return Border3::getUserData();
    }
}
