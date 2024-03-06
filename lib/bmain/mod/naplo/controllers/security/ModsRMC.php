<?php

namespace mod\naplo\controllers\security;

use hws\rmc\Controller3;

class ModsRMC extends Controller3
{

    public function list()
    {
        // $perms = getUserPerms();
        // $perms =  (array_key_exists(getEntity(),$perms)) ? $perms[getEntity()] : [];
        return collect(config('mods'))
        // ->filter(function($v,$k)use($perms){
        //     if($k == 'admin' && isSysAdmin()) return true;
        //     if(array_key_exists($k,$perms))return true;
        //     return false;
        // })
        ->sortBy('sort')->filter(function ($v, $k) {
            if ($k == 'start') return false;
            return true;
        })->map(function ($r) {
            return $r['name'];
        })->toArray();
    }
}
