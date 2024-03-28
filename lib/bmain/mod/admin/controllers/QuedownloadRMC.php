<?php

namespace mod\admin\controllers;

use hws\CacheQueue;
use Illuminate\Http\Request;

class QuedownloadRMC
{
    public function list(Request $request)
    {
        return CacheQueue::isReady();
    }

    public function download(Request $request)
    {
        return CacheQueue::download();
    }
}
