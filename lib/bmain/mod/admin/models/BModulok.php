<?php

namespace mod\admin\models;

use hws\rmc\Model;

class BModulok extends Model
{
    protected $table = 'b_modulok';

    protected $primaryKey = 'b_modulok_id';

    static $latin = true;

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
        "azon",
        "verzio",
        "b_menu_id",
        "friss_datum",
        "friss_ido",
        "modulnev",
        "rejtve",
        "csomag",
        "last_changed",
    ];

    protected $casts = [
        "b_modulok_id" => "integer",
        "azon" => "string",
        "verzio" => "string",
        "b_menu_id" => "integer",
        // "friss_datum" => "date:Y.m.d.",
        // "friss_ido" => "time",
        "modulnev" => "string",
        "rejtve" => "boolean",
        "csomag" => "string",
        // "last_changed" => "datetime:Y.m.d. H:i:s",
    ];

    protected $validation = [
        "azon" => "required|max:30",
        "verzio" => "required|max:20",
        "b_menu_id" => "required|integer|digits_between:1,11",
        "friss_datum" => "nullable|date",
        "friss_ido" => "nullable",
        "modulnev" => "nullable|max:100",
        "rejtve" => "required",
        "csomag" => "nullable|max:100",
        "last_changed" => "nullable|date",
    ];

    protected $labels = [
        "azon" => "Azonosító",
        "verzio" => "Verzió",
        "modulnev" => "Megnevezés",
    ];

}