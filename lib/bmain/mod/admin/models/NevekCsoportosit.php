<?php

namespace mod\admin\models;

use hws\rmc\Model;

class NevekCsoportosit extends Model
{
    protected $table = 'nevek_csoportosit';

    protected $primaryKey = 'nevek_csoportosit_id';

    public $timestamps = false;

    static $latin = true;

    public function defaultCollection(){
        return $this->toArray();
    }

    public function comboCollection()
    {
        return $this->toArray();
    }    

    protected $fillable = [
        "csoport_id",
        "nevek_id",
    ];

    protected $casts = [
        "nevek_csoportosit_id" => "integer",
        "csoport_id" => "integer",
        "nevek_id" => "integer",
    ];

    protected $validation = [
        "csoport_id" => "required|integer|digits_between:1,11",
        "nevek_id" => "required|integer|digits_between:1,11",
    ];

    protected $labels = [
        "nevek_csoportosit_id" => "Azonosító",
    ];

    public function Nevek()
    {
        return $this->belongsTo('App\Models\Nevek', 'nevek_id', 'id');
    }    
}