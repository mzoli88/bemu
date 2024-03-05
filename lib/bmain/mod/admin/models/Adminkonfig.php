<?php

namespace mod\admin\models;

use hws\rmc\Model;

class Adminkonfig extends Model
{
    protected $table = 'b_emu_param';

    protected $primaryKey = null;
    public $incrementing = false;

    public $timestamps = false;

    static $cache = false;
    
    static function getParams(){
        if(self::$cache)return self::$cache;
        $params = [];
        self::all()->each(function($rec) use (&$params){
            $p = $rec->getAttributes(); // sajnos nem tudja kivenni p -tulajdonságot normálisan
            $params[$p['p']] = $rec->v;
        });
        return $params;
    }

    static function setParams(Array $params){
        foreach($params as $p => $v){
            $p = self::query()->where('p',$p)->first();
            if(!$p){
                $p = new self;
                $p->p = $p;
            }
            $p->v = $v;
            $p->save();
        }
    }

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
        "p",
        "v",
    ];

    protected $casts = [
        "p" => "string",
        "v" => "string",
    ];

    protected $validation = [
        "p" => "required|max:255",
        "v" => "nullable|max:255",
    ];

    protected $labels = [
        "id" => "Azonosító",
    ];

}