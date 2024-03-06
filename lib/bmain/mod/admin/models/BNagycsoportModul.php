<?php

namespace mod\admin\models;

use hws\rmc\Model;

class BNagycsoportModul extends Model
{
    protected $table = 'b_nagycsoport_modul';

    protected $primaryKey = 'b_nagycsoport_modul_id';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
        "b_nagycsoport_id",
        "modul_azon",
    ];

    protected $casts = [
        "b_nagycsoport_modul_id" => "integer",
        "b_nagycsoport_id" => "integer",
        "modul_azon" => "string",
    ];

    protected $validation = [
        "b_nagycsoport_id" => "required|integer|digits_between:1,11",
        "modul_azon" => "required|max:30",
    ];

    protected $labels = [
        "b_nagycsoport_modul_id" => "Azonosító",
        "modul_azon" => "Modul azonosító",
    ];

}