<?php

namespace mod\admin\models;

use hws\rmc\Model;
use Illuminate\Support\Facades\DB;

class Csoport extends Model
{
    protected $table = 'nevek_csoport';

    protected $primaryKey = 'csoport_id';

    public $timestamps = false;

    public function defaultCollection(){
        return $this->toArray();
    }

    protected $fillable = [
        "nev",
        "ugyfeladmin",
        "modul_azon",
    ];

    protected $casts = [
        "csoport_id" => "integer",
        "nev" => "string",
        "ugyfeladmin" => "boolean",
        "modul_azon" => "string",
    ];

    protected $validation = [
        "nev" => "required|max:100",
        "ugyfeladmin" => "required",
        "modul_azon" => "nullable|max:30",
    ];

    protected $labels = [
        "nev" => "Jogosultság",
    ];

}