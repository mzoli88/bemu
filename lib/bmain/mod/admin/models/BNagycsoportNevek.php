<?php

namespace mod\admin\models;

use hws\rmc\Model;

class BNagycsoportNevek extends Model
{
    protected $table = 'b_nagycsoport_nevek';

    protected $primaryKey = 'b_nagycsoport_nevek_id';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
        "b_nagycsoport_id",
        "nevek_id",
    ];

    protected $casts = [
        "b_nagycsoport_nevek_id" => "integer",
        "b_nagycsoport_id" => "integer",
        "nevek_id" => "integer",
    ];

    protected $validation = [
        "b_nagycsoport_id" => "required|integer|digits_between:1,11",
        "nevek_id" => "required|integer|digits_between:1,11",
    ];

    protected $labels = [
        "b_nagycsoport_nevek_id" => "Azonosító",
    ];

    public function Nevek()
    {
        return $this->belongsTo('App\Models\Nevek', 'nevek_id', 'id');
    } 

}