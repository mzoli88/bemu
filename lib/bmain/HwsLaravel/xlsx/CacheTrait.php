<?php

namespace hws\xlsx;

use Illuminate\Support\Facades\Cache;


trait CacheTrait
{

    public $lastCacheTime = 0;


    public function canWriteCache()
    {
        if (!config('callq')) return false;

        //háttér folyamat leállítása, ha az életjel megszűnt
        // if (!Cache::has('workQuequeSignal_u_' . config('user_id')))  exit;

        // ha 4 másodpercnél régebben írtuk cache-t akkor engedjük írni a következőt
        return ((microtime(true) - $this->lastCacheTime) > 4);
    }


    public function writeCache(string $cache_text)
    {
        $this->lastCacheTime = microtime(true);
        // Cache::put('workQuequeSignal_u_' . config('user_id'), $cache_text, now()->addMinutes(5));
    }
}
