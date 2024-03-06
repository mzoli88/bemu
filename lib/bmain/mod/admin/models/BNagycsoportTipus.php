<?php

namespace mod\admin\models;

use hws\rmc\Model;

class BNagycsoportTipus extends Model
{
    protected $table = 'b_nagycsoport_tipus';

    protected $primaryKey = 'b_nagycsoport_tipus_id';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
        "tipusnev",
    ];

    protected $casts = [
        "b_nagycsoport_tipus_id" => "integer",
        "tipusnev" => "string",
    ];

    protected $validation = [
        "tipusnev" => "nullable|max:50",
    ];

    protected $labels = [
        "b_nagycsoport_tipus_id" => "Azonosító",
        "tipusnev" => "Típus",
    ];

}