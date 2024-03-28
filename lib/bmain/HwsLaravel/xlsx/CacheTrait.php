<?php

namespace hws\xlsx;

use hws\CacheQueue;
use Illuminate\Support\Facades\Cache;


trait CacheTrait
{

    public $lastCacheTime = 0;


    public function canWriteCache()
    {
        if (!config('callq')) return false;
        // ha 5 másodpercnél régebben írtuk cache-t akkor engedjük írni a következőt
        return ((microtime(true) - $this->lastCacheTime) > 5);
    }


    public function writeCache(string $cache_text)
    {
        $this->lastCacheTime = microtime(true);
        Cache::put('workQuequeSignal_u_' . getUserId(), $cache_text, now()->addMinutes(5));
    }
}
